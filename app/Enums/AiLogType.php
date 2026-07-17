<?php

namespace App\Enums;

enum AiLogType: string
{
    case ConnectionTest = 'connection_test';
    case Prompt = 'prompt';
    case ModelFetch = 'model_fetch';
    case CategoryEnrichment = 'category_enrichment';
    case BrandEnrichment = 'brand_enrichment';
    case UnitEnrichment = 'unit_enrichment';
    case PackingUnitEnrichment = 'packing_unit_enrichment';
    case AttributeEnrichment = 'attribute_enrichment';
    case ControlEnrichment = 'control_enrichment';
    case ProductEnrichment = 'product_enrichment';
    case CompanyProfileEnrichment = 'company_profile_enrichment';
    case SupplierEnrichment = 'supplier_enrichment';
    case CustomerEnrichment = 'customer_enrichment';
    case UserEnrichment = 'user_enrichment';
    case ExpenseEnrichment = 'expense_enrichment';

    public function label(): string
    {
        return match ($this) {
            self::ConnectionTest => __('agricart.ai.logs.types.connection_test'),
            self::Prompt => __('agricart.ai.logs.types.prompt'),
            self::ModelFetch => __('agricart.ai.logs.types.model_fetch'),
            self::CategoryEnrichment => __('agricart.ai.logs.types.category_enrichment'),
            self::BrandEnrichment => __('agricart.ai.logs.types.brand_enrichment'),
            self::UnitEnrichment => __('agricart.ai.logs.types.unit_enrichment'),
            self::PackingUnitEnrichment => __('agricart.ai.logs.types.packing_unit_enrichment'),
            self::AttributeEnrichment => __('agricart.ai.logs.types.attribute_enrichment'),
            self::ControlEnrichment => __('agricart.ai.logs.types.control_enrichment'),
            self::ProductEnrichment => __('agricart.ai.logs.types.product_enrichment'),
            self::CompanyProfileEnrichment => __('agricart.ai.logs.types.company_profile_enrichment'),
            self::SupplierEnrichment => __('agricart.ai.logs.types.supplier_enrichment'),
            self::CustomerEnrichment => __('agricart.ai.logs.types.customer_enrichment'),
            self::UserEnrichment => __('agricart.ai.logs.types.user_enrichment'),
            self::ExpenseEnrichment => __('agricart.ai.logs.types.expense_enrichment'),
        };
    }
}
