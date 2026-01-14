I will implement the missing DTOs to standardize data transfer across the application, following the pattern of existing DTOs (readonly class, `fromRequest` method, strict typing).

1.  **Create `BudgetTemplateDTO`** (`app/DTOs/Budget/BudgetTemplateDTO.php`)
    *   Fields: `name`, `slug`, `description`, `category`, `template_data`, `default_items`, `variables`, `estimated_hours`, `is_public`, `is_active`, etc.
    *   Will handle JSON array casting for `template_data`, `default_items`, `variables`.

2.  **Create `BudgetVersionDTO`** (`app/DTOs/Budget/BudgetVersionDTO.php`)
    *   Fields: `budget_id`, `version_number`, `changes_description`, `budget_data`, `items_data`, `version_total`, `is_current`, `version_date`.
    *   Will handle array casting for data fields.

3.  **Create `CustomerInteractionDTO`** (`app/DTOs/Customer/CustomerInteractionDTO.php`)
    *   Fields: `customer_id`, `type`, `title`, `description`, `direction`, `interaction_date`, `outcome`, `next_action`, etc.
    *   Will use `Carbon` for date parsing.

4.  **Create `AlertSettingDTO`** (`app/DTOs/Settings/AlertSettingDTO.php`)
    *   **Note**: Will create `app/DTOs/Settings` directory.
    *   Fields: `alert_type`, `metric_name`, `severity`, `threshold_value`, `notification_channels`, etc.

5.  **Create `MerchantOrderDTO`** (`app/DTOs/Payment/MerchantOrderDTO.php`)
    *   Fields: `merchant_order_id`, `provider_id`, `plan_subscription_id`, `status`, `order_status`, `total_amount`.

6.  **Update Checklist**
    *   Mark the analysis as completed and list the created DTOs in `CHECKLIST_REFATORACAO.md`.

All DTOs will extend `App\DTOs\AbstractDTO` and implement `fromRequest` and `toArray` methods.