export const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

export const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-BD', {
        maximumFractionDigits: 2,
        minimumFractionDigits: 2
    }).format(amount || 0);
};
