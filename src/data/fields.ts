import { FormField, ServerFieldType, elementId, FormCondition } from './form-structure';

/**
 * Typed factories for `form_structure` elements.
 *
 * Tests compose forms from these instead of hand-writing JSON, e.g.:
 *   formStructure([ text({ name: 'full_name', required: true }), email({ name: 'email' }) ])
 */

type Base = Partial<Pick<FormField, 'label' | 'cssClass' | 'sendEmail' | 'required'>> & {
  name: string;
  conditions?: FormCondition[];
  conditionLogic?: 'all' | 'any';
};

function make(type: ServerFieldType, name: string, extra: Partial<FormField>): FormField {
  return {
    id: elementId(),
    type,
    name,
    label: extra.label ?? defaultLabel(type, name),
    required: extra.required ?? false,
    cssClass: extra.cssClass ?? '',
    sendEmail: extra.sendEmail ?? false,
    ...extra,
  };
}

function defaultLabel(type: ServerFieldType, name: string): string {
  const nice = name.replace(/[_-]+/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
  return nice || type;
}

export function text(
  opts: Base & {
    placeholder?: string;
    minLength?: number | string;
    maxLength?: number | string;
    allowNumber?: boolean;
    allowSpecial?: boolean;
    allowSpace?: boolean;
    pattern?: string;
  },
): FormField {
  const { name, minLength, maxLength, ...rest } = opts;
  return make('text', name, {
    ...rest,
    placeholder: opts.placeholder ?? '',
    minLength: minLength != null ? String(minLength) : undefined,
    maxLength: maxLength != null ? String(maxLength) : undefined,
  });
}

export function textarea(
  opts: Base & { placeholder?: string; minLength?: number | string; maxLength?: number | string },
): FormField {
  const { name, minLength, maxLength, ...rest } = opts;
  return make('textarea', name, {
    ...rest,
    placeholder: opts.placeholder ?? '',
    minLength: minLength != null ? String(minLength) : undefined,
    maxLength: maxLength != null ? String(maxLength) : undefined,
  });
}

export function number(
  opts: Base & {
    placeholder?: string;
    minValue?: number | string;
    maxValue?: number | string;
    allowDecimal?: boolean;
    limitByChar?: boolean;
    minLength?: number | string;
    maxLength?: number | string;
  },
): FormField {
  const { name, minValue, maxValue, minLength, maxLength, ...rest } = opts;
  return make('number', name, {
    ...rest,
    placeholder: opts.placeholder ?? '',
    minValue: minValue != null ? String(minValue) : undefined,
    maxValue: maxValue != null ? String(maxValue) : undefined,
    minLength: minLength != null ? String(minLength) : undefined,
    maxLength: maxLength != null ? String(maxLength) : undefined,
  });
}

export function email(opts: Base & { placeholder?: string }): FormField {
  return make('email', opts.name, { ...opts, placeholder: opts.placeholder ?? '' });
}

export function tel(opts: Base & { placeholder?: string }): FormField {
  return make('tel', opts.name, { ...opts, placeholder: opts.placeholder ?? '' });
}

export function select(opts: Base & { options: string[] }): FormField {
  return make('select', opts.name, opts);
}

export function radio(opts: Base & { options: string[] }): FormField {
  return make('radio', opts.name, opts);
}

export function checkbox(opts: Base & { options: string[] }): FormField {
  return make('checkbox', opts.name, opts);
}

export function date(opts: Base & { start_date?: string; end_date?: string }): FormField {
  return make('date', opts.name, opts);
}

export function title(name: string, titleText: string): FormField {
  return make('title', name, { label: titleText, title: titleText });
}

export function description(name: string, html: string): FormField {
  return make('description', name, { label: html.replace(/<[^>]+>/g, '') || 'Description', description: html });
}

export function newLine(name = `nl_${Date.now()}`): FormField {
  return make('new_line', name, { label: 'New line' });
}

export function pageBreak(name = `pb_${Date.now()}`): FormField {
  return make('page_break', name, { label: 'Page break' });
}

export function hidden(name: string, value: string): FormField {
  return make('hidden_field', name, { label: name, hiddenValue: value });
}
