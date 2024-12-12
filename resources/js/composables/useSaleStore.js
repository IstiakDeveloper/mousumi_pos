import { ref } from 'vue'

export function useSaleStore() {
    const lastSale = ref(null)
    const showSuccessModal = ref(false)

    const setLastSale = (sale) => {
        lastSale.value = sale
    }

    const resetSale = () => {
        lastSale.value = null
    }

    return {
        lastSale,
        showSuccessModal,
        setLastSale,
        resetSale
    }
}
