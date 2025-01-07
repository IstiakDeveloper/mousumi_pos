import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function useCategory() {
    const form = useForm({
        name: '',
        parent_id: '',
        description: '',
        image: null,
        status: true,
    });

    const showModal = ref(false);
    const editing = ref(false);
    const imagePreview = ref(null);

    const openModal = () => {
        editing.value = false;
        form.reset();
        form.clearErrors();
        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
        form.reset();
        form.clearErrors();
        editing.value = false;
        imagePreview.value = null;
    };

    const submit = () => {
        if (editing.value) {
            form.put(route('admin.categories.update', editing.value), {
                onSuccess: () => {
                    closeModal();
                    imagePreview.value = null;
                },
                preserveScroll: true,
                preserveState: true,
                forceFormData: true,
            });
        } else {
            form.post(route('admin.categories.store'), {
                onSuccess: () => {
                    closeModal();
                    imagePreview.value = null;
                },
                preserveScroll: true,
                preserveState: true,
                forceFormData: true,
            });
        }
    };

    const editCategory = (category) => {
        editing.value = category.id;
        form.reset();
        form.name = category.name;
        form.parent_id = category.parent_id || '';
        form.description = category.description;
        form.status = Boolean(category.status);
        form.image = category.image;
        showModal.value = true;
    };

    return {
        form,
        showModal,
        editing,
        imagePreview,
        openModal,
        closeModal,
        submit,
        editCategory,
    };
}
