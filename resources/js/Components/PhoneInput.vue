<script setup lang="ts">
import {
    AsYouType,
    getCountries,
    getCountryCallingCode,
    parsePhoneNumberFromString,
    type CountryCode,
} from 'libphonenumber-js';
import { computed, onMounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        id?: string;
        error?: string;
        disabled?: boolean;
        /** ISO alpha-2 used when no country has been picked yet. */
        defaultCountry?: string;
        placeholder?: string;
    }>(),
    {
        id: undefined,
        error: undefined,
        disabled: false,
        defaultCountry: 'US',
        placeholder: '',
    },
);

// v-model: the canonical E.164 string (empty when no/invalid number).
const model = defineModel<string | null>({ default: '' });
// v-model:country: ISO 3166-1 alpha-2 of the picked country.
const country = defineModel<string>('country', { default: '' });

const FALLBACK_COUNTRY: CountryCode = 'US';

// The formatted national number the user sees/edits.
const national = ref('');

// Set once the incoming value has been hydrated, so country/default watchers
// don't clobber state during the initial render.
let initialized = false;

const supportedCountries = new Set(getCountries());

function isSupported(code?: string | null): code is CountryCode {
    return !!code && supportedCountries.has(code as CountryCode);
}

/** The country actually used for parsing/formatting (with sensible fallbacks). */
function currentCountry(): CountryCode {
    const candidate = (
        country.value ||
        props.defaultCountry ||
        ''
    ).toUpperCase();

    return isSupported(candidate)
        ? (candidate as CountryCode)
        : FALLBACK_COUNTRY;
}

const regionNames =
    typeof Intl !== 'undefined' && 'DisplayNames' in Intl
        ? new Intl.DisplayNames(['en'], { type: 'region' })
        : null;

function countryName(code: string): string {
    try {
        return regionNames?.of(code) ?? code;
    } catch {
        return code;
    }
}

/** Convert an ISO alpha-2 code to its flag emoji (regional indicators). */
function flagEmoji(code: string): string {
    return code
        .toUpperCase()
        .replace(/./g, (char) =>
            String.fromCodePoint(0x1f1e6 - 65 + char.charCodeAt(0)),
        );
}

const countryOptions = computed(() =>
    getCountries()
        .map((code) => ({
            code,
            dialCode: getCountryCallingCode(code),
            flag: flagEmoji(code),
            name: countryName(code),
        }))
        .sort((a, b) => a.name.localeCompare(b.name)),
);

/** Reformat the national field and recompute the E.164 model value. */
function recompute(raw: string): void {
    const formatter = new AsYouType(currentCountry());
    national.value = formatter.input(raw);

    const parsed = formatter.getNumber();
    // `parsed.number` is E.164; emit it for any in-progress number so an
    // incomplete entry surfaces a validation error rather than vanishing.
    model.value = parsed ? parsed.number : '';
}

function onNationalInput(event: Event): void {
    recompute((event.target as HTMLInputElement).value);
}

function onCountryChange(event: Event): void {
    country.value = (event.target as HTMLSelectElement).value;
}

// Re-derive the number whenever the selected country changes (keeps the dial
// code in sync with the same digits the user already typed).
watch(country, () => {
    if (!initialized) {
        return;
    }

    recompute(national.value.replace(/\D/g, ''));
});

// While the field is still empty, follow the form's default country (e.g. the
// organization's country) so the dropdown starts on the most likely choice.
watch(
    () => props.defaultCountry,
    (next) => {
        if (!initialized || national.value) {
            return;
        }

        if (isSupported(next?.toUpperCase())) {
            country.value = next!.toUpperCase();
        }
    },
);

function hydrate(): void {
    const incoming = model.value;

    if (incoming) {
        const parsed = parsePhoneNumberFromString(incoming);

        if (parsed) {
            if (isSupported(parsed.country)) {
                country.value = parsed.country;
            } else if (!isSupported(country.value)) {
                country.value = currentCountry();
            }

            national.value = new AsYouType(currentCountry()).input(
                parsed.nationalNumber,
            );

            initialized = true;

            return;
        }
    }

    // No (usable) incoming value: make sure the country model holds a real code
    // so the dropdown and a later national entry resolve correctly.
    if (!isSupported(country.value)) {
        country.value = currentCountry();
    }

    initialized = true;
}

onMounted(hydrate);
</script>

<template>
    <div>
        <div class="flex">
            <select
                :value="currentCountry()"
                :disabled="disabled"
                aria-label="Country code"
                class="w-40 shrink-0 rounded-l-md border-input bg-muted text-sm focus:z-10 focus:border-ring focus:ring-ring"
                :class="error ? 'border-destructive/40' : 'border-input'"
                @change="onCountryChange"
            >
                <option
                    v-for="opt in countryOptions"
                    :key="opt.code"
                    :value="opt.code"
                >
                    {{ opt.flag }} {{ opt.name }} (+{{ opt.dialCode }})
                </option>
            </select>
            <input
                :id="id"
                :value="national"
                :disabled="disabled"
                type="tel"
                autocomplete="tel-national"
                :placeholder="placeholder"
                class="-ml-px block w-full flex-1 rounded-r-md shadow-sm focus:border-ring focus:ring-ring sm:text-sm"
                :class="error ? 'border-destructive/40' : 'border-input'"
                @input="onNationalInput"
            />
        </div>
        <p v-if="error" class="mt-1 text-sm text-destructive">{{ error }}</p>
    </div>
</template>
