<?php


use oat\generis\test\TestCase;
use oat\generis\model\data\Ontology;
use oat\oatbox\service\ServiceManager;
use \oat\tao\model\TaoOntology;
use PHPUnit\Framework\MockObject\MockObject;
use oat\tao\elasticsearch\Watcher\ElasticDocumentBuilderFactory;
use \oat\tao\model\search\index\DocumentBuilder\IndexDocumentBuilderInterface;
use \oat\tao\elasticsearch\Watcher\Resources\ItemIndexDocumentBuilder;
use \oat\tao\elasticsearch\Watcher\Resources\UnclassifiedIndexDocumentBuilder;

class ElasticDocumentBuilderFactoryTest extends TestCase
{
    /** @var Ontology */
    private $ontology;
    
    /** @var ServiceManager|MockObject */
    private $service;
    
    /** @var DocumentBuilderFactoryInterface $factory */
    private $factory;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->ontology = $this->createMock(Ontology::class);
        
        $this->service = $this->createMock(ServiceManager::class);
        
        $this->service->expects($this->any())
            ->method('get')
            ->with(Ontology::SERVICE_ID)
            ->willReturnCallback(
                function () {
                    $property = $this->createMock(core_kernel_classes_Property::class);
                    $property->expects($this->any())->method('getPropertyValues')->willReturn(
                        []
                    );
                    
                    $ontology = $this->createMock(Ontology::class);
                    $ontology->expects($this->any())->method('getProperty')->willReturn(
                        $property
                    );
                    return $ontology;
                }
            );
        
        ServiceManager::setServiceManager($this->service);
        
        $this->factory = new ElasticDocumentBuilderFactory();
    }
    
    public function testGetDocumentBuilderByResourceType()
    {
        $resource = $this->createMock(
            core_kernel_classes_Resource::class
        );
    
        $resource->expects($this->any())->method('getTypes')->willReturn(
            [
                TaoOntology::CLASS_URI_ITEM => []
            ]
        );
    
        $resourceTypes = $resource->getTypes();
        $resourceType = current(array_keys($resourceTypes)) ?: '';
    
        /** @var IndexDocumentBuilderInterface $documentBuilder */
        $documentBuilder = $this->factory->getDocumentBuilderByResourceType($resourceType);
    
        $this->assertInstanceOf(IndexDocumentBuilderInterface::class, $documentBuilder);
    }
    
    public function testGetDocumentBuilderByItemResourceType()
    {
        $resource = $this->createMock(
            core_kernel_classes_Resource::class
        );
    
        $resource->expects($this->any())->method('getTypes')->willReturn(
            []
        );
    
        $resourceTypes = $resource->getTypes();
        $resourceType = current(array_keys($resourceTypes)) ?: '';
    
        /** @var IndexDocumentBuilderInterface $documentBuilder */
        $documentBuilder = $this->factory->getDocumentBuilderByResourceType($resourceType);
    
        $this->assertInstanceOf(IndexDocumentBuilderInterface::class, $documentBuilder);
    }
}
