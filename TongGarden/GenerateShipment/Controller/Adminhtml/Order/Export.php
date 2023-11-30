<?php

namespace TongGarden\GenerateShipment\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\OrderFactory;

class Export extends Action
{
    protected $orderRepository;
    protected $fileFactory;
    protected $fileSystem;
    protected $file;
    protected $searchCriteriaBuilder;
    protected $orderFactory;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        FileFactory $fileFactory,
        \Magento\Framework\Filesystem $fileSystem,
        File $file,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderFactory $orderFactory
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->fileFactory = $fileFactory;
        $this->fileSystem = $fileSystem;
        $this->file = $file;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderFactory = $orderFactory;
    }

    public function execute()
    {
        try {
            // Build search criteria
            $searchCriteria = $this->searchCriteriaBuilder->create();
    
            // Fetch orders based on the search criteria
            $orders = $this->orderRepository->getList($searchCriteria);
    
            // Debugging: Print the number of orders fetched
            echo 'Number of orders fetched: ' . $orders->getTotalCount();
    
            $csvData = [['Order ID', 'Customer Email', 'Order Total']];
    
            foreach ($orders->getItems() as $order) {
                // Debugging: Print order ID
                echo 'Order ID: ' . $order->getId();
    
                try {
                    // Load the full order model using OrderFactory
                    $loadedOrder = $this->orderFactory->create()->load($order->getId());
    
                    // Debugging: Print loaded order data
                    echo 'Loaded Order Data: ';
                    print_r($loadedOrder->getData());
    
                    // Use $loadedOrder for further processing
                    $csvData[] = [
                        $loadedOrder->getId(),
                        $loadedOrder->getCustomerEmail(),
                        $loadedOrder->getGrandTotal(),
                    ];
                } catch (\Exception $e) {
                    // Handle any exceptions during order loading
                    echo 'Error loading order: ' . $e->getMessage();
                }
            }
    
            // Debugging: Print CSV data
            echo 'CSV Data: ';
            print_r($csvData);
    
            // File generation and response
            $fileName = 'orders.csv';
            $filePath = 'export/' . $fileName;
            
            $directory = $this->fileSystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $directory->create('export'); // Ensure the export directory exists
            
            $stream = $directory->openFile($filePath, 'w+');
            foreach ($csvData as $rowData) {
                $stream->writeCsv($rowData);
            }
            $stream->close();
            
            $fileContents = file_get_contents($directory->getAbsolutePath($filePath));
            
            $response = $this->getResponse();
            $response->setHttpResponseCode(200)
                ->setHeader('Content-type', 'text/csv')
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                ->setHeader('Content-Disposition', 'attachment; filename=' . $fileName)
                ->setHeader('Expires', '0')
                ->setHeader('Pragma', 'public')
                ->setHeader('Content-Length', strlen($fileContents));
            
            $response->clearBody();
            $response->sendHeaders();
            $response->setBody($fileContents);
            
            
            return $response;
                } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while exporting orders: %1', $e->getMessage()));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->error($e);
            return $this->_redirect('generateship/index/index');
        }
    }
}