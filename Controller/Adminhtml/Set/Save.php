<?php

declare(strict_types=1);

namespace Smile\ScopedEav\Controller\Adminhtml\Set;

use Magento\Backend\App\Action\Context;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Eav\Api\Data\AttributeSetInterfaceFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Registry;
use Smile\ScopedEav\Controller\Adminhtml\AbstractSet;

/**
 * Scoped EAV entity attribute set admin save controller.
 */
class Save extends AbstractSet
{
    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Constructor.
     *
     * @param Context $context Context.
     * @param Registry $registry Registry.
     * @param Config $eavConfig EAV config.
     * @param AttributeSetRepositoryInterface $attributeSetRepository Attribute set repository.
     * @param AttributeSetInterfaceFactory $attributeSetFactory Attribute set factory.
     * @param FilterManager $filterManager Filters.
     * @param Data $jsonHelper JSON helper.
     * @param JsonFactory $resultJsonFactory Result JSON factory.
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Config $eavConfig,
        AttributeSetRepositoryInterface $attributeSetRepository,
        AttributeSetInterfaceFactory $attributeSetFactory,
        FilterManager $filterManager,
        Data $jsonHelper,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context, $registry, $eavConfig, $attributeSetRepository, $attributeSetFactory);

        $this->filterManager       = $filterManager;
        $this->jsonHelper          = $jsonHelper;
        $this->resultJsonFactory   = $resultJsonFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function execute()
    {
        $hasError = false;
        $isNewSet = $this->getRequest()->getParam('gotoEdit', false) == '1';

        $attributeSet = $this->getAttributeSet();

        try {
            $attributeSet->setAttributeSetName($this->filterManager->stripTags((string) $this->getRequest()->getParam('attribute_set_name')));

            if ($isNewSet === false) {
                $data = $this->jsonHelper->jsonDecode($this->getRequest()->getPost('data'));
                $data['attribute_set_name'] = $this->filterManager->stripTags((string) $data['attribute_set_name']);
                $attributeSet->organizeData($data);
            }

            $attributeSet->validate();

            if ($isNewSet) {
                $attributeSet->save();
                $attributeSet->initFromSkeleton($this->getRequest()->getParam('skeleton_set'));
            }

            $attributeSet->save();
            $this->messageManager->addSuccessMessage(__('You saved the attribute set.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $hasError = true;
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the attribute set.'));
            $hasError = true;
        }

        $response = $hasError ? $this->getErrorResponse() : $this->getSuccessResponse();

        if ($isNewSet) {
            $response = $this->getNewAttributeSetResponse($attributeSet);
        }

        return $response;
    }

    /**
     * Redirect user on new attribute set save.
     *
     * @param AttributeSetInterface $attributeSet Attribute set.
     *
     * @return ResponseInterface
     */
    private function getNewAttributeSetResponse($attributeSet): ResponseInterface
    {
        $resultRedirect = $this->_redirect('*/*/add');

        if ($attributeSet && $attributeSet->getId()) {
            $resultRedirect = $this->_redirect('*/*/edit', ['id' => $attributeSet->getId()]);
        }

        return $resultRedirect;
    }

    /**
     * Return formatted JSON success response.
     *
     * @return Json
     */
    private function getSuccessResponse(): Json
    {
        $response = ['error' => 0, 'url' => $this->getUrl('*/*/index')];

        return $this->resultJsonFactory->create()->setData($response);
    }

    /**
     * Return formatted JSON error response.
     *
     * @return Json
     */
    private function getErrorResponse(): Json
    {
        $layout = $this->_view->getLayout();
        $layout->initMessages();
        $response = ['error' => 1, 'message' => $layout->getMessagesBlock()->getGroupedHtml()];

        return $this->resultJsonFactory->create()->setData($response);
    }
}
