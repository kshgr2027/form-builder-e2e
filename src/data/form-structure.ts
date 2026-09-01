/**
 * Shape of one element inside `form_templates.form_structure`.
 *
 * Mirrors what `form-builder-v3.js#buildFormStructure()` emits and what
 * `FormSubmissionController::generateValidationRules()` + `form-renderer.js`
 * consume. Only the fields relevant to the in-scope types are modelled.
 */
export type ServerFieldType =
  | 'text'
  | 'textarea'
  | 'number'
  | 'email'
  | 'tel'
  | 'select'
  | 'radio'
  | 'checkbox'
  | 'date'
  | 'title'
  | 'description'
  | 'new_line'
  | 'hidden_field'
  | 'page_break';

export interface FormCondition {
  field: string; // name of the controlling field
  operator:
    | 'equals'
    | 'not_equals'
    | 'contains'
    | 'not_contains'
    | 'greater_than'
    | 'less_than'
    | 'in'
    | 'not_in';
  value: string;
}

export interface FormField {
  id: string;
  type: ServerFieldType;
  label: string;
  name: string;
  required: boolean;
  cssClass: string;
  sendEmail: boolean;

  placeholder?: string;
  minLength?: string;
  maxLength?: string;
  pattern?: string;

  allowSpecial?: boolean;
  allowNumber?: boolean;
  allowSpace?: boolean;

  limitByChar?: boolean;
  allowDecimal?: boolean;
  minValue?: string;
  maxValue?: string;

  options?: string[];

  start_date?: string;
  end_date?: string;

  title?: string;
  description?: string;
  hiddenValue?: string;

  conditions?: FormCondition[];
  conditionLogic?: 'all' | 'any';
}

export type FormStructure = FormField[];

let seq = 0;
export function elementId(): string {
  return `element_${Date.now()}_${seq++}`;
}
