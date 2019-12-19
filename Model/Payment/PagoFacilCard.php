<?php

declare(strict_types=1);

namespace PagoFacil\Payment\Model\Payment;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Cc;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use PagoFacil\Payment\Exceptions\AmountException;
use PagoFacil\Payment\Exceptions\ClientException;
use PagoFacil\Payment\Exceptions\PaymentException;
use PagoFacil\Payment\Model\Payment\Interfaces\Card;
use PagoFacil\Payment\Source\Client\ClientInterface;
use PagoFacil\Payment\Source\Client\EndPoint;
use PagoFacil\Payment\Source\Client\Interfaces\PagoFacilResponseInterface;
use PagoFacil\Payment\Source\Client\PagoFacil as Client;
use PagoFacil\Payment\Source\Client\PrimitiveRequest;
use PagoFacil\Payment\Source\Transaction\Charge;
use PagoFacil\Payment\Source\User\Client as UserClient;
use PagoFacil\Payment\Source\Interfaces\Dto;
use PagoFacil\Payment\Source\Logger as PagoFacilLogger;
use Psr\Http\Message\RequestInterface;

class PagoFacilCard extends Cc implements Card
{
    use PagoFacilLogger;

    /** @var EndPoint $endpoint */
    private $endpoint;
    /** @var UserClient $user */
    private $user;
    /** @var Client $client  */
    private $client;

    /**
     * PagoFacilCard constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param ModuleListInterface $moduleList
     * @param TimezoneInterface $localeDate
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ModuleListInterface $moduleList,
        TimezoneInterface $localeDate,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->createLocalLogger(static::CODE);
        $this->_isGateway = true;
        $this->_canCapture = true;
        $this->_canAuthorize = true;
        $this->_canCapturePartial = true;
        $this->_canRefund = true;
        $this->_code = static::CODE;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );

        if ($this->getConfigData('is_sandbox')) {
            $url = $this->getConfigData('endpoint_sandbox');
        } else {
            $url = $this->getConfigData('endpoint_production');
        }

        $this->endpoint = new EndPoint(
            $url,
            $this->getConfigData('uri_transaction')
        );

        $this->user = new UserClient(
            $this->getConfigData('display_user_id'),
            $this->getConfigData('display_user_branch_office_id'),
            $this->getConfigData('display_user_phase_id'),
            $this->endpoint
        );

        $this->client = new Client($this->user->getEndpoint()->getCompleteUrl());
    }

    /**
     * @param Order $order
     * @param Payment $payment
     * @return RequestInterface
     */
    private function createRequestTransaction(Order $order, Payment $payment): RequestInterface
    {
        return new PrimitiveRequest(
            ClientInterface::POST,
            PrimitiveRequest::transformData($order, $payment, $this->user),
            []
        );
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return self
     * @throws AmountException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(InfoInterface $payment, $amount): self
    {
        $this->zendLogger->debug('holis, desde capture');
        /** @var Payment $payment */
        /** @var Order $order */

        if ($amount <= 0) {
            throw new AmountException('Invalid amount');
        }

        try {
            if (is_null($payment->getParentTransactionId())) {
                $this->authorize($payment, $amount);
            }
            $payment->setIsTransactionClosed(true);
        } catch (ClientException $exception) {
            $payment->setIsTransactionClosed(false);
            $payment->setIsTransactionPending(true);
            $payment->save();
        } catch (PaymentException $exception) {
            $payment->setTransactionId($exception->getCharge()->getId());
            $payment->setIsTransactionClosed(false);
            $payment->setIsTransactionPending(true);
            $payment->save();
        }

        return $this;
    }

    public function authorize(InfoInterface $payment, $amount)
    {
        $this->zendLogger->debug('holis, desde auth');
        /** @var Payment $payment */
        /** @var Order $order */
        /** @var Charge $charge */

        $request = $this->createRequestTransaction($payment->getOrder(), $payment);

        $response = $this->client->sendRequest($request);

        $charge = $this->getTransaction($response);

        $payment->setTransactionId($charge->getId());
        $payment->setParentTransactionId($charge->getId());
        $payment->setIsTransactionClosed(false);

        return $this;
    }

    /**
     * @param PagoFacilResponseInterface $response
     * @return Dto
     */
    public function getTransaction(PagoFacilResponseInterface $response): Dto
    {
        return $response->getTransaction();
    }
}
