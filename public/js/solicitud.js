document.addEventListener('DOMContentLoaded', function() {
    const proveedorSelect = document.getElementById('proveedor');
    const categoriaSelect = document.getElementById('categoria');

    proveedorSelect.addEventListener('change', function() {
        const proveedorId = this.value;

        if (proveedorId) {
            fetch(`/proveedor/${proveedorId}/categorias`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    categoriaSelect.innerHTML = '<option value="">Seleccione una categoría</option>';
                    if (Array.isArray(data)) {
                        data.forEach(categoria => {
                            const option = document.createElement('option');
                            option.value = categoria.idcategorias;
                            option.textContent = categoria.nombre_categoria;
                            categoriaSelect.appendChild(option);
                        });
                    } else {
                        console.error('Unexpected response format:', data);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        } else {
            categoriaSelect.innerHTML = '<option value="">Seleccione una categoría</option>';
        }
    });
});
