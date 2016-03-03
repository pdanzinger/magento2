<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ConfigurableProduct\Test\Unit\Controller\Adminhtml\Product\Initialization\Helper\Plugin;

use Magento\ConfigurableProduct\Controller\Adminhtml\Product\Initialization\Helper\Plugin\UpdateConfigurations;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\VariationHandler;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper as ProductInitializationHelper;
use Magento\Catalog\Model\Product;

class UpdateConfigurationsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UpdateConfigurations
     */
    private $updateConfigurations;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepositoryMock;

    /**
     * @var VariationHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $variationHandlerMock;

    /**
     * @var ProductInitializationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    protected function setUp()
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $this->productRepositoryMock = $this->getMockBuilder(ProductRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->variationHandlerMock = $this->getMockBuilder(VariationHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectMock = $this->getMockBuilder(ProductInitializationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->updateConfigurations = $this->objectManagerHelper->getObject(
            UpdateConfigurations::class,
            [
                'request' => $this->requestMock,
                'productRepository' => $this->productRepositoryMock,
                'variationHandler' => $this->variationHandlerMock
            ]
        );
    }

    public function testAfterInitialize()
    {
        $productMock = $this->getProductMock();
        $configurableMatrix = [
            [
                'newProduct' => true,
                'id' => 'product1'
            ],
            [
                'newProduct' => false,
                'id' => 'product2',
                'status' => 'simple2_status',
                'sku' => 'simple2_sku',
                'name' => 'simple2_name',
                'price' => '3.33',
                'configurable_attribute' => 'simple2_configurable_attribute',
                'weight' => '5.55'
            ],
            [
                'newProduct' => false,
                'id' => 'product3',
                'qty' => '3'
            ]
        ];
        $configurations = [
            'product2' => [
                'status' => 'simple2_status',
                'sku' => 'simple2_sku',
                'name' => 'simple2_name',
                'price' => '3.33',
                'configurable_attribute' => 'simple2_configurable_attribute',
                'weight' => '5.55'
            ],
            'product3' => [
                'status' => '',
                'sku' => '',
                'name' => '',
                'price' => '',
                'configurable_attribute' => '',
                'weight' => '',
                'quantity_and_stock_status' => ['qty' => '3']
            ]
        ];
        /** @var Product[]|\PHPUnit_Framework_MockObject_MockObject[] $productMocks */
        $productMocks = [
            'product2' => $this->getProductMock($configurations['product2'], true),
            'product3' => $this->getProductMock($configurations['product3'])
        ];

        $this->requestMock->expects(static::any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['store', 0, 0],
                    ['configurable-matrix', [], $configurableMatrix]
                ]
            );
        $this->variationHandlerMock->expects(static::once())
            ->method('duplicateImagesForVariations')
            ->with($configurations)
            ->willReturn($configurations);
        $this->productRepositoryMock->expects(static::any())
            ->method('getById')
            ->willReturnMap(
                [
                    ['product2', false, 0, false, $productMocks['product2']],
                    ['product3', false, 0, false, $productMocks['product3']]
                ]
            );
        $this->variationHandlerMock->expects(static::any())
            ->method('processMediaGallery')
            ->willReturnMap(
                [
                    [$productMocks['product2'], $configurations['product2'], $configurations['product2']],
                    [$productMocks['product3'], $configurations['product3'], $configurations['product3']]
                ]
            );

        $this->assertSame($productMock, $this->updateConfigurations->afterInitialize($this->subjectMock, $productMock));
    }

    /**
     * Get product mock
     *
     * @param array $expectedData
     * @param bool $hasDataChanges
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProductMock(array $expectedData = null, $hasDataChanges = false)
    {
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($expectedData !== null) {
            $productMock->expects(static::once())
                ->method('addData')
                ->with($expectedData)
                ->willReturnSelf();
        }

        $productMock->expects(static::any())
            ->method('hasDataChanges')
            ->willReturn($hasDataChanges);
        $productMock->expects($hasDataChanges ? static::once() : static::never())
            ->method('save')
            ->willReturnSelf();

        return $productMock;
    }
}
