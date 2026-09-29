{{-- Knopf sperren und „Verbinde …“ anzeigen, während die Bank antwortet. --}}
<script>
    document.querySelectorAll('[data-busy-form]').forEach(form => form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        if (!button) return;
        button.disabled = true;
        button.textContent = 'Verbinde mit der Bank …';
    }));
</script>
