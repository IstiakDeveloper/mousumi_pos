const pad = (value) => String(value).padStart(2, '0')

const fromParts = (year, month, day) => `${pad(day)}-${pad(month)}-${year}`

export const formatDate = (value) => {
    if (value === null || value === undefined || value === '') {
        return ''
    }

    if (value instanceof Date && !Number.isNaN(value.getTime())) {
        return fromParts(value.getFullYear(), value.getMonth() + 1, value.getDate())
    }

    const raw = String(value).trim()
    const dateOnly = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/)
    const dateAtMidnight = raw.match(/^(\d{4})-(\d{2})-(\d{2})[ T]00:00:00(?:\.0+)?Z?$/)
    const matched = dateOnly || dateAtMidnight
    if (matched) {
        return fromParts(matched[1], matched[2], matched[3])
    }

    const bd = raw.match(/^(\d{2})-(\d{2})-(\d{4})$/)
    if (bd) {
        return `${bd[1]}-${bd[2]}-${bd[3]}`
    }

    const parsed = new Date(raw)
    if (Number.isNaN(parsed.getTime())) {
        return raw
    }

    return fromParts(parsed.getFullYear(), parsed.getMonth() + 1, parsed.getDate())
}

export const formatDateTime = (value) => {
    if (value === null || value === undefined || value === '') {
        return ''
    }

    const parsed = value instanceof Date ? value : new Date(value)
    if (Number.isNaN(parsed.getTime())) {
        return formatDate(value)
    }

    let hours = parsed.getHours()
    const suffix = hours >= 12 ? 'PM' : 'AM'
    hours = hours % 12 || 12

    return `${formatDate(parsed)} ${pad(hours)}:${pad(parsed.getMinutes())} ${suffix}`
}

export const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-BD', {
        maximumFractionDigits: 2,
        minimumFractionDigits: 2
    }).format(amount || 0);
};
