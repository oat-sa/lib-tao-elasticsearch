<?php


use oat\generis\test\TestCase;
use oat\generis\model\data\Ontology;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\search\index\IndexService;
use oat\tao\model\search\index\IndexDocument;
use \oat\tao\model\TaoOntology;
use PHPUnit\Framework\MockObject\MockObject;
use oat\tao\elasticsearch\Watcher\Resources\ItemIndexDocumentBuilder;
use oat\tao\model\search\index\DocumentBuilder\AbstractIndexDocumentBuilder;
use oat\tao\elasticsearch\Watcher\ElasticDocumentBuilderFactory;

class ItemIndexDocumentBuilderTest extends TestCase
{
    /** @var Ontology */
    private $ontology;
    
    /** @var ServiceManager|MockObject */
    private $service;
    
    /** @var IndexService $indexService */
    private $indexService;
    
    private const ARRAY_RESOURCE = [
        'id' => '#fakeUri',
        'body' => [
            'type' => []
        ]
    ];
    
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
    
                    $resource = $this->createMock(core_kernel_classes_Resource::class);
                    $resource->expects($this->any())->method('getTypes')->willReturn(
                        []
                    );
                    $ontology->expects($this->any())->method('getResource')->willReturn(
                        $resource
                    );
    
                    $class = $this->createMock(core_kernel_classes_Class::class);
                    $ontology->expects($this->any())->method('getClass')->willReturn(
                        $class
                    );
                    
                    return $ontology;
                }
            );
        
        ServiceManager::setServiceManager($this->service);
    }
    
    private function getIndexService()
    {
        if (!$this->indexService) {
            $this->indexService = new IndexService();
            $this->indexService->setOption(IndexService::OPTION_DOCUMENT_BUILDER_FACTORY, (new ElasticDocumentBuilderFactory()));
            $this->indexService->setServiceLocator($this->service);
        }
        
        return $this->indexService;
    }
    
    public function testCreateDocumentFromResource()
    {
        $builder = new ItemIndexDocumentBuilder();
    
        $resource = $this->createMock(
            core_kernel_classes_Resource::class
        );
    
        $resource->expects($this->any())->method('getTypes')->willReturn(
            [
                TaoOntology::CLASS_URI_ITEM => []
            ]
        );
        $resource->expects($this->any())->method('getUri')->willReturn(
            '#fakeUri'
        );
        $resource->expects($this->any())->method('getLabel')->willReturn(
            'FakeResource'
        );
    
        $class = $this->createMock(
            core_kernel_classes_Class::class
        );
        $class->expects($this->any())->method('getUri')->willReturn(
            TaoOntology::CLASS_URI_ITEM
        );
        $class->expects($this->any())->method('getLabel')->willReturn(
            'Item'
        );
        
        $resource->expects($this->any())->method('getPropertiesValues')->willReturn(
            [
                'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' => [
                    TaoOntology::CLASS_URI_ITEM => $class
                ],
                'http://www.tao.lu/Ontologies/TAOItem.rdf#ItemModel' => [
                    ''
                ]
            ]
        );
    
        $document = $builder->createDocumentFromResource(
            $resource
        );
        
        $this->assertInstanceOf(IndexDocument::class, $document);
    
        $this->assertEquals('#fakeUri', $document->getId());
        
        $this->assertEquals([
            'type' => $class->getUri(),
            'label' => $resource->getLabel(),
            'class' => $class->getLabel(),
            'content' => null,
            'model' => ''
        ], $document->getBody());
        
        $this->assertEquals([], (array)$document->getDynamicProperties());
    }
}
