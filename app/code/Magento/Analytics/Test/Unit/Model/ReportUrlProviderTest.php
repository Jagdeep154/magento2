<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Analytics\Test\Unit\Model;

use Magento\Analytics\Model\AnalyticsConnector\OTPRequest;
use Magento\Analytics\Model\AnalyticsToken;
use Magento\Analytics\Model\ReportUrlProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReportUrlProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /**
     * @var AnalyticsToken|\PHPUnit_Framework_MockObject_MockObject
     */
    private $analyticsTokenMock;

    /**
     * @var OTPRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    private $otpRequestMock;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ReportUrlProvider
     */
    private $reportUrlProvider;

    /**
     * @var string
     */
    private $urlReportConfigPath = 'path/url/report';

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->configMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->analyticsTokenMock = $this->getMockBuilder(AnalyticsToken::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->otpRequestMock = $this->getMockBuilder(OTPRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->reportUrlProvider = $this->objectManagerHelper->getObject(
            ReportUrlProvider::class,
            [
                'config' => $this->configMock,
                'analyticsToken' => $this->analyticsTokenMock,
                'otpRequest' => $this->otpRequestMock,
                'urlReportConfigPath' => $this->urlReportConfigPath,
            ]
        );
    }

    /**
     * @param bool $isTokenExist
     * @param string|null $otp If null OTP was not received.
     *
     * @dataProvider getUrlDataProvider
     */
    public function testGetUrl($isTokenExist, $otp)
    {
        $reportUrl = 'https://example.com/report';
        $url = '';

        $this->configMock
            ->expects($this->once())
            ->method('getValue')
            ->with($this->urlReportConfigPath)
            ->willReturn($reportUrl);
        $this->analyticsTokenMock
            ->expects($this->once())
            ->method('isTokenExist')
            ->with()
            ->willReturn($isTokenExist);
        $this->otpRequestMock
            ->expects($isTokenExist ? $this->once() : $this->never())
            ->method('call')
            ->with()
            ->willReturn($otp);
        if ($isTokenExist && $otp) {
            $url = $reportUrl . '?' . http_build_query(['otp' => $otp], '', '&');
        }
        $this->assertSame($url ?: $reportUrl, $this->reportUrlProvider->getUrl());
    }

    /**
     * @return array
     */
    public function getUrlDataProvider()
    {
        return [
            'TokenDoesNotExist' => [false, null],
            'TokenExistAndOtpEmpty' => [true, null],
            'TokenExistAndOtpValid' => [true, '249e6b658877bde2a77bc4ab'],
        ];
    }
}
