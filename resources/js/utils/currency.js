export function formatCurrency(value) {
    const amount = Number(value || 0).toLocaleString('en-BD', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    })
    return `৳${amount}`
  }
