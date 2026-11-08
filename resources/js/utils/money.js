const formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

/** Formats a decimal string/number coming from Laravel ("12.50") as currency. */
export function formatMoney(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return formatter.format(Number(value));
}
