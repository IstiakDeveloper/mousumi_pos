export const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

export const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: 'BDT' // Change this to your preferred currency
    }).format(amount)
}
