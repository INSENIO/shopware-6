<?php

declare(strict_types=1);

namespace PayonePayment\Test\Payone\Request\Capture;

use DMS\PHPUnitExtensions\ArraySubset\Assert;
use PayonePayment\Components\DependencyInjection\Factory\PaymentHandlerFactory;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentMethod\PayoneCreditCard;
use PayonePayment\Payone\Request\Capture\CaptureRequest;
use PayonePayment\Payone\Request\Capture\CaptureRequestFactory;
use PayonePayment\Struct\PaymentTransaction;
use PayonePayment\Test\Constants;
use PayonePayment\Test\Mock\Factory\RequestFactoryTestTrait;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\Currency\CurrencyEntity;
use Symfony\Component\HttpFoundation\ParameterBag;

class CaptureRequestFactoryTest extends TestCase
{
    use RequestFactoryTestTrait;

    public function testCorrectFullCaptureRequestParameters(): void
    {
        $factory = new CaptureRequestFactory($this->getSystemRequest(), $this->getCaptureRequest(), new PaymentHandlerFactory());

        $request = $factory->getFullRequest($this->getPaymentTransaction(), new ParameterBag(), Context::createDefaultContext());

        Assert::assertArraySubset(
            [
                'aid'             => '',
                'amount'          => 10000,
                'api_version'     => '3.10',
                'currency'        => 'EUR',
                'encoding'        => 'UTF-8',
                'integrator_name' => 'shopware6',
                'key'             => '',
                'mid'             => '',
                'mode'            => '',
                'portalid'        => '',
                'request'         => 'capture',
                'sequencenumber'  => 1,
                'solution_name'   => 'kellerkinder',
                'txid'            => 'test-transaction-id',
            ],
            $request
        );

        $this->assertArrayHasKey('integrator_version', $request);
        $this->assertArrayHasKey('solution_version', $request);
    }

    public function testCorrectPartialCaptureRequestParameters(): void
    {
        $factory = new CaptureRequestFactory($this->getSystemRequest(), $this->getCaptureRequest(), new PaymentHandlerFactory());

        $request = $factory->getPartialRequest($this->getPaymentTransaction(), new ParameterBag([
            'amount' =>  100
        ]), Context::createDefaultContext());

        Assert::assertArraySubset(
            [
                'aid'             => '',
                'amount'          => 10000,
                'api_version'     => '3.10',
                'currency'        => 'EUR',
                'encoding'        => 'UTF-8',
                'integrator_name' => 'shopware6',
                'key'             => '',
                'mid'             => '',
                'mode'            => '',
                'portalid'        => '',
                'request'         => 'capture',
                'sequencenumber'  => 1,
                'solution_name'   => 'kellerkinder',
                'txid'            => 'test-transaction-id',
            ],
            $request
        );

        $this->assertArrayHasKey('integrator_version', $request);
        $this->assertArrayHasKey('solution_version', $request);
    }

    protected function getPaymentTransaction(): PaymentTransaction
    {
        $orderTransactionEntity = new OrderTransactionEntity();
        $orderTransactionEntity->setId(Constants::ORDER_TRANSACTION_ID);
$orderTransactionEntity->setPaymentMethodId(PayoneCreditCard::UUID);

        $orderEntity = new OrderEntity();
        $orderEntity->setId(Constants::ORDER_ID);
        $orderEntity->setOrderNumber(Constants::ORDER_NUMBER);
        $orderEntity->setSalesChannelId(Defaults::SALES_CHANNEL);
        $orderEntity->setAmountTotal(100);
        $orderEntity->setCurrencyId(Constants::CURRENCY_ID);
        $orderTransactionEntity->setOrder($orderEntity);

        $paymentMethodEntity = new PaymentMethodEntity();
        $paymentMethodEntity->setHandlerIdentifier(PayoneCreditCardPaymentHandler::class);
        $orderTransactionEntity->setPaymentMethod($paymentMethodEntity);

        $customFields = [
            CustomFieldInstaller::TRANSACTION_ID  => Constants::PAYONE_TRANSACTION_ID,
            CustomFieldInstaller::SEQUENCE_NUMBER => 0,
        ];
        $orderTransactionEntity->setCustomFields($customFields);

        return PaymentTransaction::fromOrderTransaction($orderTransactionEntity);
    }

    private function getCaptureRequest(): CaptureRequest
    {
        $currencyRepository = $this->createMock(EntityRepository::class);
        $currencyEntity     = new CurrencyEntity();
        $currencyEntity->setId(Constants::CURRENCY_ID);
        $currencyEntity->setIsoCode('EUR');
        $currencyEntity->setDecimalPrecision(2);
        $currencyRepository->method('search')->willReturn(
            new EntitySearchResult(
                1,
                new EntityCollection([$currencyEntity]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        return new CaptureRequest($currencyRepository);
    }
}
