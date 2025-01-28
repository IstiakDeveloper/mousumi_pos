export const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

export const formatCurrency = (amount) => {
    // Format the number first
    const formattedNumber = new Intl.NumberFormat('en-BD', {
        maximumFractionDigits: 2,
        minimumFractionDigits: 2
    }).format(amount || 0)

    // Add the Taka symbol (৳)
    return `৳${formattedNumber}`
}
