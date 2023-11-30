<?php

namespace TongGarden\GenerateShipment\Block;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\UrlInterface;
use Magento\Customer\Model\Session;

class Orders extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    protected $orderCollectionFactory;
    protected $urlBuilder;
    protected $customerSession;

    public function __construct(
        UrlInterface $urlBuilder,
        Session $customerSession,
        CollectionFactory $orderCollectionFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->customerSession = $customerSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($context, $data);
    }

    public function getExportUrl()
    {
        $customerId = $this->customerSession->getCustomerId();
        $params = ['customer_id' => $customerId];

        return $this->urlBuilder->getUrl('generateship/index/index/', $params);
    }

    public function getOrderCollectionByDateRange()
    {

        $fromDate = $this->getRequest()->getParam('from_date');
        $toDate = $this->getRequest()->getParam('to_date');
   
        $orderCollection = $this->orderCollectionFactory->create()
        ->addFieldToFilter(
            'created_at',
            ['from' => $fromDate, 'to' => $toDate]
        )
        ->addAttributeToFilter('status', ['in' => 'pending']);

        $orderData = $orderCollection->getData();
        return $orderData;


    }
}
