import { FormStructure } from './form-structure';
import * as f from './fields';

/**
 * Ready-made form structures for common scenarios.
 * Each returns a fresh array (new element ids) so tests never share state.
 */

/** One required text field + one optional text field. Smallest useful form. */
export function minimalTextForm(): FormStructure {
  return [
    f.text({ name: 'full_name', label: 'Full name', required: true }),
    f.text({ name: 'nickname', label: 'Nickname', required: false }),
  ];
}

/** A broad "contact" form touching most in-scope field types. */
export function contactForm(): FormStructure {
  return [
    f.title('intro', 'Contact us'),
    f.text({ name: 'full_name', label: 'Full name', required: true }),
    f.email({ name: 'email', label: 'Email address', required: true }),
    f.tel({ name: 'phone', label: 'Phone', required: false }),
    f.number({ name: 'age', label: 'Age', required: false, minValue: 18, maxValue: 120 }),
    f.select({ name: 'topic', label: 'Topic', required: true, options: ['Sales', 'Support', 'Other'] }),
    f.radio({ name: 'contact_pref', label: 'Preferred contact', required: true, options: ['Email', 'Phone'] }),
    f.checkbox({ name: 'interests', label: 'Interests', required: false, options: ['News', 'Offers', 'Events'] }),
    f.textarea({ name: 'message', label: 'Message', required: true }),
  ];
}

/** Field with configured character constraints (DEF-02 territory). */
export function constrainedTextForm(): FormStructure {
  return [
    f.text({
      name: 'code',
      label: 'Access code',
      required: true,
      minLength: 5,
      maxLength: 10,
      allowNumber: false,
      allowSpecial: false,
      allowSpace: false,
    }),
  ];
}

/** Optional email + a required text field (DEF-01: email still rendered required). */
export function optionalEmailForm(): FormStructure {
  return [
    f.text({ name: 'full_name', label: 'Full name', required: true }),
    f.email({ name: 'email', label: 'Email (optional)', required: false }),
  ];
}

/** Number field with a numeric range (DEF-06: not enforced server-side). */
export function numberRangeForm(): FormStructure {
  return [f.number({ name: 'quantity', label: 'Quantity', required: true, minValue: 1, maxValue: 10 })];
}

/** Select with a fixed option list (DEF-07: value not whitelisted server-side). */
export function fixedOptionsForm(): FormStructure {
  return [f.select({ name: 'colour', label: 'Colour', required: true, options: ['Red', 'Green', 'Blue'] })];
}

/** Date field with an allowed range (DEF-08: range/format not enforced server-side). */
export function dateRangeForm(): FormStructure {
  return [
    f.date({
      name: 'event_date',
      label: 'Event date',
      required: true,
      start_date: '2026-01-01',
      end_date: '2026-12-31',
    }),
  ];
}

/** Conditional: `details` shows only when `need_details` = "Yes". */
export function conditionalForm(): FormStructure {
  return [
    f.radio({ name: 'need_details', label: 'Provide details?', required: true, options: ['Yes', 'No'] }),
    f.textarea({
      name: 'details',
      label: 'Details',
      required: true,
      conditions: [{ field: 'need_details', operator: 'equals', value: 'Yes' }],
      conditionLogic: 'all',
    }),
  ];
}

/** Two pages separated by a page break. */
export function twoPageForm(): FormStructure {
  return [
    f.text({ name: 'first_name', label: 'First name', required: true }),
    f.pageBreak('pb1'),
    f.text({ name: 'last_name', label: 'Last name', required: true }),
  ];
}
