<?php

declare(strict_types=1);

namespace Smile\ScopedEav\Ui\DataProvider\Entity\Form\Modifier\Helper;

use Magento\Catalog\Model\Attribute\ScopeOverriddenValue;
use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeGroupInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Phrase;
use Smile\ScopedEav\Api\Data\AttributeInterface;
use Smile\ScopedEav\Api\Data\EntityInterface;
use Smile\ScopedEav\ViewModel\Data as DataViewModel;

/**
 * Scoped EAV form modifier EAV helper.
 */
class Eav
{
    /**
     * @var AttributeGroupRepositoryInterface
     */
    private $attributeGroupRepository;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var SearchCriteriaBuilder $searchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder $searchCriteriaBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var DataViewModel
     */
    private $dataViewModel;

    /**
     * @var ScopeOverriddenValue
     */
    private $scopeOverriddenValue;

    /**
     * @var AttributeGroupInterface[]
     */
    private $attributeGroups = [];

    /**
     * @var AttributeInterface[]
     */
    private $attributes = [];

    /**
     * @var array
     */
    private $canDisplayUseDefault = [];

    /**
     * Constructor.
     *
     * @param AttributeGroupRepositoryInterface $attributeGroupRepository Attribute group repository.
     * @param AttributeRepositoryInterface $attributeRepository Attribute repository.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder Search criteria builder.
     * @param SortOrderBuilder $sortOrderBuilder Sort order builder.
     * @param ScopeOverriddenValue $scopeOverriddenValue Scope attribute helper.
     * @param DataViewModel $dataViewModel Scoped EAV data view model.
     */
    public function __construct(
        AttributeGroupRepositoryInterface $attributeGroupRepository,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        ScopeOverriddenValue $scopeOverriddenValue,
        DataViewModel $dataViewModel
    ) {
        $this->attributeGroupRepository = $attributeGroupRepository;
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->scopeOverriddenValue = $scopeOverriddenValue;
        $this->dataViewModel = $dataViewModel;
    }

    /**
     * List of attribute group by attribute set id.
     *
     * @param int|string $attributeSetId Attribute set id.
     *
     * @return AttributeGroupInterface[]
     */
    public function getGroups($attributeSetId): array
    {
        if (!isset($this->attributeGroups[$attributeSetId])) {
            $this->attributeGroups[$attributeSetId] = [];
            $searchCriteria = $this->prepareGroupSearchCriteria($attributeSetId)->create();

            $attributeGroupSearchResult = $this->attributeGroupRepository->getList($searchCriteria);

            foreach ($attributeGroupSearchResult->getItems() as $group) {
                $groupCode = $group->getAttributeGroupCode();
                $this->attributeGroups[$attributeSetId][$groupCode] = $group;
            }
        }

        return $this->attributeGroups[$attributeSetId];
    }

    /**
     * List of attribute by attribute set id.
     *
     * @param EntityInterface $entity Entity.
     * @param int|string $attributeSetId Attribute set id.
     *
     * @return AttributeInterface[]
     */
    public function getAttributes(EntityInterface $entity, $attributeSetId): array
    {
        if (!isset($this->attributes[$attributeSetId])) {
            $this->attributes[$attributeSetId] = [];
            foreach ($this->getGroups($attributeSetId) as $group) {
                $groupCode = $group->getAttributeGroupCode();
                $this->attributes[$attributeSetId][$groupCode] = $this->loadAttributes($entity, $group);
            }
        }

        return $this->attributes[$attributeSetId];
    }

    /**
     * Scope label for an attribute.
     *
     * @param AttributeInterface $attribute Attribute.
     *
     * @return string|Phrase
     */
    public function getScopeLabel(AttributeInterface $attribute)
    {
        return $this->dataViewModel->getScopeLabel($attribute);
    }

    /**
     * Check if attribute is global.
     *
     * @param AttributeInterface $attribute Attribute.
     *
     * @return boolean
     */
    public function isScopeGlobal(AttributeInterface $attribute): bool
    {
        return $this->dataViewModel->isScopeGlobal($attribute);
    }

    /**
     * Check if the attribute value have been overriden for the current store.
     *
     * @param EntityInterface    $entity    Entity
     * @param AttributeInterface $attribute Attribute.
     * @param int                $storeId   Store id.
     *
     * @return boolean
     */
    public function hasValueForStore(EntityInterface $entity, AttributeInterface $attribute, $storeId): bool
    {
        $hasValue = false;
        $attributeCode = $attribute->getAttributeCode();
        $interface = $this->dataViewModel->getEntityInterface($entity);

        try {
            $hasValue = $hasValue || $this->scopeOverriddenValue->containsValue($interface, $entity, $attributeCode, $storeId);
        } catch (\Exception $e) {
            // Catch exception hasValueForStore function
        }

        return $hasValue;
    }

    /**
     * Can the use default checkbox be displayed for an attribute.
     *
     * @param AttributeInterface $attribute Attribute.
     * @param EntityInterface    $entity    Entity.
     *
     * @return mixed
     */
    public function canDisplayUseDefault(AttributeInterface $attribute, EntityInterface $entity): mixed
    {
        $attributeCode = $attribute->getAttributeCode();

        if (!isset($this->canDisplayUseDefault[$attributeCode])) {
            $isGlobal = $attribute->getScope() == $attribute::SCOPE_GLOBAL_TEXT;
            $this->canDisplayUseDefault[$attributeCode] = (!$isGlobal && $entity->getId() && $entity->getStoreId() > 0);
        }

        return $this->canDisplayUseDefault[$attributeCode];
    }

    /**
     * Return form element by frontend input.
     *
     * @param string $frontendInput Frontend input.
     *
     * @return string|NULL
     */
    public function getFormElement(string $frontendInput): ?string
    {
        return $this->dataViewModel->getFormElement($frontendInput);
    }

    /**
     * Prepare a search criteria that filter group by attribute set.
     *
     * @param int|string $attributeSetId Attribute set id.
     *
     * @return SearchCriteriaBuilder
     */
    private function prepareGroupSearchCriteria($attributeSetId): SearchCriteriaBuilder
    {
        return $this->searchCriteriaBuilder->addFilter(AttributeGroupInterface::ATTRIBUTE_SET_ID, $attributeSetId);
    }

    /**
     * Return attribute list for a group.
     *
     * @param EntityInterface         $entity Entity.
     * @param AttributeGroupInterface $group  Attribute group.
     *
     * @return \Magento\Eav\Api\Data\AttributeInterface[]
     */
    private function loadAttributes(EntityInterface $entity, AttributeGroupInterface $group)
    {
        $attributes = [];

        $sortOrder = $this->sortOrderBuilder
            ->setField('sort_order')
            ->setAscendingDirection()
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(AttributeGroupInterface::GROUP_ID, $group->getAttributeGroupId())
            ->addSortOrder($sortOrder)
            ->create();

        $entityTypeCode = $this->dataViewModel->getEntityMetadata($entity)->getEavEntityType();
        $groupAttributes = $this->attributeRepository->getList($entityTypeCode, $searchCriteria)->getItems();

        foreach ($groupAttributes as $attribute) {
            $attributes[] = $attribute;
        }

        return $attributes;
    }
}
