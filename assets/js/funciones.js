document.addEventListener("DOMContentLoaded", function () {
    $('#tbl').DataTable({
        language: {
            "url": "//cdn.datatables.net/plug-ins/1.10.11/i18n/Spanish.json"
        },
        "order": [
            [0, "desc"]
        ]
    });
    
    // ============================================
    // DETECTAR TURNO AUTOMÁTICAMENTE
    // ============================================
    function detectarTurno() {
        const ahora = new Date();
        const hora = ahora.getHours();
        const minutos = ahora.getMinutes();
        const horaCompleta = hora + (minutos / 60);
        
        let turno = '';
        let icono = '';
        let colorClass = '';
        
        // Definición de turnos:
        // Mañana: 6:00 AM - 11:59 AM
        // Tarde: 12:00 PM - 5:59 PM (18:00)
        // Noche: 6:00 PM - 5:59 AM (siguiente día)
        
        if (horaCompleta >= 6 && horaCompleta < 12) {
            turno = 'mañana';
            icono = '🌅';
            colorClass = 'text-warning';
        } else if (horaCompleta >= 12 && horaCompleta < 18) {
            turno = 'tarde';
            icono = '☀️';
            colorClass = 'text-info';
        } else {
            turno = 'noche';
            icono = '🌙';
            colorClass = 'text-primary';
        }
        
        // Actualizar los campos
        $('#turno').val(turno);
        $('#turno_display').val(icono + ' ' + turno.charAt(0).toUpperCase() + turno.slice(1));
        $('#turno_display').removeClass('text-warning text-info text-primary').addClass(colorClass);
        
        return turno;
    }
    
    // Detectar turno al cargar la página si existe el campo turno
    // PERO NO en la página de reportes (donde queremos "Todos los turnos" por defecto)
    if ($('#turno').length > 0 && !$('#turno').closest('form').find('button[onclick*="generarReporte"]').length) {
        detectarTurno();
        
        // Actualizar cada 60 segundos por si cambia el turno
        setInterval(detectarTurno, 60000);
    }
    
    $(".confirmar").submit(function (e) {
        e.preventDefault();
        Swal.fire({
            title: 'Esta seguro de eliminar?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'SI, Eliminar!'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        })
    })
    $("#nom_cliente").autocomplete({
        minLength: 3,
        source: function (request, response) {
            $.ajax({
                url: "ajax.php",
                dataType: "json",
                data: {
                    q: request.term
                },
                success: function (data) {
                    response(data);
                }
            });
        },
        select: function (event, ui) {
            $("#idcliente").val(ui.item.id);
            $("#nom_cliente").val(ui.item.label);
            $("#tel_cliente").val(ui.item.telefono);
            $("#dir_cliente").val(ui.item.direccion);
        }
    })
    $("#producto").autocomplete({
        minLength: 1,
        source: function (request, response) {
            $.ajax({
                url: "ajax.php",
                dataType: "json",
                data: {
                    pro: request.term
                },
                success: function (data) {
                    response(data);
                }
            });
        },
        // Desactivar el filtrado local de jQuery UI para que use solo el servidor
        autoFocus: true,
        select: function (event, ui) {
            $("#id").val(ui.item.id);
            $("#producto").val(ui.item.descripcion);
            $("#precio").val(formatearMoneda(parseFloat(ui.item.precio)));
            $("#cantidad").val(1); // Auto-completar con 1
            $("#cantidad").focus();
            $("#cantidad").select(); // Seleccionar el texto para fácil edición
            
            // Calcular el subtotal automáticamente
            const precio = parseFloat(ui.item.precio);
            $("#sub_total").val(formatearMoneda(precio));
            return false; // Evitar que se llene con el value
        }
    })

    $('#btn_generar').click(function (e) {
        e.preventDefault();
        var rows = $('#tblDetalle tr').length;
        if (rows > 2) {
            var action = 'procesarVenta';
            var id = $('#idcliente').val();
            var metodo_pago = $('#metodo_pago').val();
            var monto_pagado = limpiarFormato($('#monto_pagado').val());
            var turno = $('#turno').val();
            var descuento_global = parseFloat($('#descuento_global').val()) || 0;
            
            // Obtener el total sin descuento
            var total_sin_descuento = 0;
            var totalText = $('#tblDetalle tfoot tr td:eq(1)').text();
            if (totalText) {
                total_sin_descuento = limpiarFormato(totalText);
            }
            
            // Obtener el total con descuento
            var total_pagar = limpiarFormato($('#total_con_descuento').val());
            
            // Validar que el total no sea 0
            if (total_pagar <= 0) {
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: 'Total inválido',
                    text: 'No se puede generar una venta con total $0',
                    showConfirmButton: true
                });
                return;
            }
            
            // Validaciones específicas para efectivo
            if (metodo_pago === 'efectivo') {
                // Validar que se haya ingresado el monto pagado
                if (!monto_pagado || monto_pagado <= 0) {
                    Swal.fire({
                        position: 'center',
                        icon: 'warning',
                        title: 'Datos de pago incompletos',
                        text: 'Debe ingresar el monto pagado por el cliente',
                        showConfirmButton: true
                    });
                    $('#monto_pagado').focus();
                    return;
                }
                
                // Validar que el monto pagado sea suficiente
                if (monto_pagado < total_pagar) {
                    Swal.fire({
                        position: 'center',
                        icon: 'warning',
                        title: 'El monto pagado es menor al total',
                        text: 'Total: ' + formatearMoneda(total_pagar) + ' - Pagado: ' + formatearMoneda(monto_pagado),
                        showConfirmButton: true
                    });
                    return;
                }
            } else {
                // Para otros métodos, el monto pagado es exacto al total
                monto_pagado = total_pagar;
            }
            
            $.ajax({
                url: 'ajax.php',
                async: true,
                data: {
                    procesarVenta: action,
                    id: id,
                    metodo_pago: metodo_pago,
                    monto_pagado: monto_pagado,
                    turno: turno,
                    descuento_global: descuento_global
                },
                success: function (response) {

                    const res = JSON.parse(response);
                    if (res.mensaje != 'error') {
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Venta Generada',
                            showConfirmButton: false,
                            timer: 2000
                        })
                        setTimeout(() => {
                            generarPDF(res.id_cliente, res.id_venta);
                            // Limpiar todos los campos del formulario
                            $('#descuento_global').val('');
                            $('#total_con_descuento').val('$0');
                            $('#monto_pagado').val('$');
                            $('#vuelto').val('$0');
                            $('#metodo_pago').val('efectivo');
                            // Esperar un poco más antes de recargar para que el PDF se abra
                            setTimeout(() => {
                                location.reload();
                            }, 500);
                        }, 500);
                    } else {
                        Swal.fire({
                            position: 'center',
                            icon: 'error',
                            title: 'Error al generar la venta',
                            text: res.detalle || 'Ocurrió un error inesperado',
                            showConfirmButton: true,
                            timer: 3000
                        })
                    }
                },
                error: function (error) {

                }
            });
        } else {
            Swal.fire({
                position: 'center',
                icon: 'warning',
                title: 'No hay producto para generar la venta',
                showConfirmButton: false,
                timer: 2000
            })
        }
    });
    
    // Manejar cambio de método de pago
    if (document.getElementById("metodo_pago")) {
        $('#metodo_pago').on('change', function() {
            const metodo = $(this).val();
            
            if (metodo === 'efectivo') {
                // Habilitar campos para efectivo
                $('#monto_pagado').prop('readonly', false).prop('required', true);
                $('#vuelto').prop('readonly', true);
                $('#monto_pagado').val('$');
                $('#vuelto').val('$0');
                $('#monto_pagado').closest('.form-group').find('label').html('<i class="fas fa-money-bill-wave"></i> Monto Pagado');
            } else {
                // Deshabilitar y auto-completar para otros métodos
                $('#monto_pagado').prop('readonly', true).prop('required', false);
                $('#vuelto').prop('readonly', true);
                
                // Auto-completar con el total exacto
                const total = limpiarFormato($('#total_con_descuento').val());
                $('#monto_pagado').val(formatearMoneda(total));
                $('#vuelto').val('$0');
                
                $('#monto_pagado').closest('.form-group').find('label').html('<i class="fas fa-money-bill-wave"></i> Monto (Auto)');
            }
        });
        
        // También actualizar cuando cambia el total (si no es efectivo)
        $('#descuento_global').on('input', function() {
            const metodo = $('#metodo_pago').val();
            if (metodo !== 'efectivo') {
                setTimeout(() => {
                    const total = limpiarFormato($('#total_con_descuento').val());
                    $('#monto_pagado').val(formatearMoneda(total));
                    $('#vuelto').val('$0');
                }, 100);
            }
        });
    }
    
    if (document.getElementById("detalle_venta")) {
        listar();
    }
})

// ============================================
// FORMATEAR NÚMEROS
// ============================================
function formatearNumero(numero) {
    return new Intl.NumberFormat('es-AR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(numero);
}

function formatearMoneda(numero) {
    return '$' + formatearNumero(numero);
}

function limpiarFormato(texto) {
    if (!texto) return 0;
    // Convertir a string y limpiar
    texto = texto.toString();
    // Eliminar símbolo de moneda
    texto = texto.replace('$', '');
    // Eliminar espacios
    texto = texto.trim();
    // Eliminar puntos (separadores de miles)
    texto = texto.replace(/\./g, '');
    // Convertir coma decimal a punto
    texto = texto.replace(',', '.');
    // Convertir a número
    return parseFloat(texto) || 0;
}

// Función para formatear inputs de moneda mientras se escribe
function formatearInputMoneda(input) {
    // Obtener el valor actual
    let valor = input.value;
    
    // Guardar la posición del cursor
    let posicionCursor = input.selectionStart;
    
    // Limpiar el valor (eliminar todo excepto números y coma)
    let valorLimpio = valor.replace(/[^\d,]/g, '');
    
    // Si está vacío, dejar solo el símbolo de moneda
    if (valorLimpio === '') {
        input.value = '$';
        setTimeout(() => input.setSelectionRange(1, 1), 0);
        return;
    }
    
    // Contar cuántas comas hay (solo permitir una)
    let comas = valorLimpio.split(',').length - 1;
    if (comas > 1) {
        // Si hay más de una coma, eliminar la última
        let partes = valorLimpio.split(',');
        valorLimpio = partes[0] + ',' + partes.slice(1).join('');
    }
    
    // Separar parte entera y decimal
    let partes = valorLimpio.split(',');
    let parteEntera = partes[0] || '';
    let parteDecimal = partes.length > 1 ? partes[1] : null;
    
    // Si solo hay 0 o está vacío, poner 0
    if (parteEntera === '' || parteEntera === '0') {
        parteEntera = '0';
    } else {
        // Eliminar ceros a la izquierda
        parteEntera = parteEntera.replace(/^0+/, '');
    }
    
    // Formatear la parte entera con puntos de miles
    let parteEnteraFormateada = '';
    let contador = 0;
    for (let i = parteEntera.length - 1; i >= 0; i--) {
        if (contador === 3) {
            parteEnteraFormateada = '.' + parteEnteraFormateada;
            contador = 0;
        }
        parteEnteraFormateada = parteEntera[i] + parteEnteraFormateada;
        contador++;
    }
    
    // Construir valor formateado
    let valorFormateado = '$' + parteEnteraFormateada;
    
    // Solo agregar la coma y decimales si el usuario escribió una coma
    if (parteDecimal !== null) {
        // Limitar decimales a 2 dígitos
        parteDecimal = parteDecimal.substring(0, 2);
        valorFormateado += ',' + parteDecimal;
    }
    
    // Calcular la nueva posición del cursor
    let puntosAntesDelCursor = 0;
    let valorSinFormato = valor.replace(/[$.]/g, '');
    
    // Contar caracteres válidos antes del cursor en el valor original
    let caracteresAntes = valor.substring(0, posicionCursor).replace(/[$.]/g, '').length;
    
    // Encontrar la posición equivalente en el valor formateado
    let caracteresContados = 0;
    let nuevaPosicion = 1; // Empezar después del $
    
    for (let i = 1; i < valorFormateado.length; i++) {
        if (valorFormateado[i] !== '.') {
            caracteresContados++;
        }
        if (caracteresContados >= caracteresAntes) {
            nuevaPosicion = i + 1;
            break;
        }
    }
    
    // Asegurar que la posición sea válida
    if (nuevaPosicion > valorFormateado.length) {
        nuevaPosicion = valorFormateado.length;
    }
    if (nuevaPosicion < 1) {
        nuevaPosicion = 1;
    }
    
    // Actualizar el valor del input
    input.value = valorFormateado;
    
    // Restaurar la posición del cursor
    setTimeout(() => {
        if (document.activeElement === input) {
            input.setSelectionRange(nuevaPosicion, nuevaPosicion);
        }
    }, 0);
}

// ============================================
// CALCULAR PRECIO Y AGREGAR PRODUCTO
// ============================================
function calcularPrecio(e) {
    e.preventDefault();
    const cant = parseFloat($("#cantidad").val()) || 0;
    const precio = limpiarFormato($('#precio').val());
    const total = cant * precio;
    
    // Formatear el subtotal
    $('#sub_total').val(formatearMoneda(total));
    
    // Si presiona Enter, agregar el producto
    if (e.which == 13) {
        agregarProductoAlCarrito();
    }
}

// Función para agregar producto (usada por Enter y por el botón)
function agregarProductoAlCarrito() {
    const cant = parseFloat($('#cantidad').val()) || 0;
    const precio = limpiarFormato($('#precio').val());
    const id = $('#id').val();
    
    if (cant > 0 && cant != '' && id != '') {
        registrarDetalle(event, id, cant, precio);
        $('#producto').focus();
    } else {
        if (!id || id == '') {
            Swal.fire({
                position: 'center',
                icon: 'warning',
                title: 'Selecciona un producto',
                showConfirmButton: false,
                timer: 1500
            });
            $('#producto').focus();
        } else {
            Swal.fire({
                position: 'center',
                icon: 'warning',
                title: 'Ingresa una cantidad válida',
                showConfirmButton: false,
                timer: 1500
            });
            $('#cantidad').focus();
        }
    }
}

// Event listener para el botón agregar
$(document).ready(function() {
    $('#btn_agregar_producto').click(function(e) {
        e.preventDefault();
        agregarProductoAlCarrito();
    });
    
    // Event listener para detectar cambios en el campo cantidad (incluyendo botones +/-)
    $('#cantidad').on('change input', function(e) {
        const cant = parseFloat($(this).val()) || 0;
        const precio = limpiarFormato($('#precio').val());
        const total = cant * precio;
        $('#sub_total').val(formatearMoneda(total));
    });
});

function listar() {
    let html = '';
    let detalle = 'detalle';
    $.ajax({
        url: "ajax.php",
        dataType: "json",
        data: {
            detalle: detalle
        },
        success: function (response) {

            response.forEach(row => {
                html += `<tr>
                <td>${row['id']}</td>
                <td>${row['descripcion']}</td>
                <td class="text-center font-weight-bold">${row['cantidad']}</td>
                <td class="text-right">${formatearMoneda(parseFloat(row['precio_venta']))}</td>
                <td class="text-right font-weight-bold text-primary">${formatearMoneda(parseFloat(row['sub_total']))}</td>
                <td><button class="btn btn-danger btn-sm" type="button" onclick="deleteDetalle(${row['id']})" title="Eliminar producto">
                <i class="fas fa-trash-alt"></i></button></td>
                </tr>`;
            });
            document.querySelector("#detalle_venta").innerHTML = html;
            calcular();
        }
    });
}

function registrarDetalle(e, id, cant, precio) {
    if (document.getElementById('producto').value != '') {
        if (id != null) {
            let action = 'regDetalle';
            $.ajax({
                url: "ajax.php",
                type: 'POST',
                dataType: "json",
                data: {
                    id: id,
                    cant: cant,
                    regDetalle: action,
                    precio: precio
                },
                success: function (response) {

                    if (response == 'registrado') {
                        $('#cantidad').val('');
                        $('#precio').val('');
                        $("#producto").val('');
                        $("#sub_total").val('');
                        $("#producto").focus();
                        listar();
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Producto Ingresado',
                            showConfirmButton: false,
                            timer: 2000
                        })
                    } else if (response == 'actualizado') {
                        $('#cantidad').val('');
                        $('#precio').val('');
                        $("#producto").val('');
                        $("#producto").focus();
                        listar();
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Producto Actualizado',
                            showConfirmButton: false,
                            timer: 2000
                        })
                    } else {
                        Swal.fire({
                            position: 'center',
                            icon: 'error',
                            title: 'Error al ingresar el producto. Verifique el stock disponible',
                            showConfirmButton: false,
                            timer: 2000
                        })
                    }
                }
            });
        }
    }
}

function deleteDetalle(id) {
    let detalle = 'Eliminar'
    $.ajax({
        url: "ajax.php",
        data: {
            id: id,
            delete_detalle: detalle
        },
        success: function (response) {

            if (response == 'restado') {
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Producto Descontado',
                    showConfirmButton: false,
                    timer: 2000
                })
                document.querySelector("#producto").value = '';
                document.querySelector("#producto").focus();
                listar();
            } else if (response == 'ok') {
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Producto Eliminado',
                    showConfirmButton: false,
                    timer: 2000
                })
                document.querySelector("#producto").value = '';
                document.querySelector("#producto").focus();
                listar();
            } else {
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: 'Error al eliminar el producto',
                    showConfirmButton: false,
                    timer: 2000
                })
            }
        }
    });
}

function calcular() {
    // obtenemos todas las filas del tbody
    var filas = document.querySelectorAll("#tblDetalle tbody tr");

    var total = 0;

    // recorremos cada una de las filas
    filas.forEach(function (e) {

        // obtenemos las columnas de cada fila
        var columnas = e.querySelectorAll("td");

        // obtenemos los valores del importe (limpiando el formato)
        // Ahora la columna del subtotal es la 4 (índice 4) porque quitamos las columnas de descuento
        var importeTexto = columnas[4].textContent;
        var importe = limpiarFormato(importeTexto);

        total += importe;
    });

    // mostramos la suma total formateada en la columna correcta del footer
    var filaTotal = document.querySelector("#tblDetalle tfoot tr");
    if (filaTotal) {
        var columnaTotal = filaTotal.querySelector("td:nth-child(2)"); // Segunda columna del footer
        if (columnaTotal) {
            columnaTotal.textContent = formatearMoneda(total);
        }
    }
    
    // Calcular el total con descuento global
    calcularTotalConDescuento();
}

function calcularTotalConDescuento() {
    // Obtener el total sin descuento del footer
    var totalTexto = $('#tblDetalle tfoot tr td:eq(1)').text();
    var total = limpiarFormato(totalTexto);
    
    // Obtener el porcentaje de descuento global
    var valorInput = $('#descuento_global').val();
    var descuento = parseFloat(valorInput) || 0;
    
    // Validar que el descuento esté entre 0 y 100
    if (valorInput !== '' && descuento < 0) {
        $('#descuento_global').val('');
        descuento = 0;
    }
    if (valorInput !== '' && descuento > 100) {
        $('#descuento_global').val(100);
        descuento = 100;
    }
    
    // Calcular el total con descuento
    var totalConDescuento = total * (1 - descuento / 100);
    
    // Mostrar el total con descuento formateado
    $('#total_con_descuento').val(formatearMoneda(totalConDescuento));
    
    // Si el método de pago no es efectivo, auto-completar el monto pagado
    if (document.getElementById('metodo_pago')) {
        const metodo = $('#metodo_pago').val();
        if (metodo !== 'efectivo') {
            $('#monto_pagado').val(formatearMoneda(totalConDescuento));
            $('#vuelto').val('$0');
        } else {
            // Recalcular el vuelto para efectivo
            calcularVuelto();
        }
    } else {
        // Recalcular el vuelto
        calcularVuelto();
    }
}

function generarPDF(cliente, id_venta) {
    url = 'pdf/generar.php?cl=' + cliente + '&v=' + id_venta;
    window.open(url, '_blank');
}
if (document.getElementById("stockMinimo")) {
    const action = "sales";
    $.ajax({
        url: 'chart.php',
        type: 'POST',
        data: {
            action
        },
        async: true,
        success: function (response) {
            if (response != 0) {
                var data = JSON.parse(response);
                var nombre = [];
                var cantidad = [];
                for (var i = 0; i < data.length; i++) {
                    nombre.push(data[i]['descripcion']);
                    cantidad.push(data[i]['cantidad']);
                }
                var ctx = document.getElementById("stockMinimo");
                var myPieChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: nombre,
                        datasets: [{
                            data: cantidad,
                            backgroundColor: ['#024A86', '#E7D40A', '#581845', '#C82A54', '#EF280F', '#8C4966', '#FF689D', '#E36B2C', '#69C36D', '#23BAC4'],
                        }],
                    },
                });
            }
        },
        error: function (error) {
            console.log(error);
        }
    });
}
if (document.getElementById("ProductosVendidos")) {
    const action = "polarChart";
    $.ajax({
        url: 'chart.php',
        type: 'POST',
        async: true,
        data: {
            action
        },
        success: function (response) {
            if (response != 0) {
                var data = JSON.parse(response);
                var nombre = [];
                var cantidad = [];
                for (var i = 0; i < data.length; i++) {
                    nombre.push(data[i]['descripcion']);
                    cantidad.push(data[i]['cantidad']);
                }
                var ctx = document.getElementById("ProductosVendidos");
                var myPieChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: nombre,
                        datasets: [{
                            data: cantidad,
                            backgroundColor: ['#C82A54', '#EF280F', '#23BAC4', '#8C4966', '#FF689D', '#E7D40A', '#E36B2C', '#69C36D', '#581845', '#024A86'],
                        }],
                    },
                });
            }
        },
        error: function (error) {
            console.log(error);

        }
    });
}

function btnCambiar(e) {
    e.preventDefault();
    const actual = document.getElementById('actual').value;
    const nueva = document.getElementById('nueva').value;
    if (actual == "" || nueva == "") {
        Swal.fire({
            position: 'center',
            icon: 'error',
            title: 'Los campos estan vacios',
            showConfirmButton: false,
            timer: 2000
        })
    } else {
        const cambio = 'pass';
        $.ajax({
            url: "ajax.php",
            type: 'POST',
            data: {
                actual: actual,
                nueva: nueva,
                cambio: cambio
            },
            success: function (response) {
                if (response == 'ok') {
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Contraseña modificada',
                        showConfirmButton: false,
                        timer: 2000
                    })
                    document.querySelector('#frmPass').reset();
                    $("#nuevo_pass").modal("hide");
                } else if (response == 'dif') {
                    Swal.fire({
                        position: 'center',
                        icon: 'error',
                        title: 'La contraseña actual incorrecta',
                        showConfirmButton: false,
                        timer: 2000
                    })
                } else {
                    Swal.fire({
                        position: 'center',
                        icon: 'error',
                        title: 'Error al modificar la contraseña',
                        showConfirmButton: false,
                        timer: 2000
                    })
                }
            }
        });
    }
}

function editarCliente(id) {
    const action = "editarCliente";
    $.ajax({
        url: 'ajax.php',
        type: 'GET',
        async: true,
        data: {
            editarCliente: action,
            id: id
        },
        success: function (response) {
            const datos = JSON.parse(response);
            $('#nombre').val(datos.nombre);
            $('#telefono').val(datos.telefono);
            $('#direccion').val(datos.direccion);
            $('#id').val(datos.idcliente);
            $('#btnAccion').val('Modificar');
        },
        error: function (error) {
            console.log(error);

        }
    });
}

function editarUsuario(id) {
    const action = "editarUsuario";
    $.ajax({
        url: 'ajax.php',
        type: 'GET',
        async: true,
        data: {
            editarUsuario: action,
            id: id
        },
        success: function (response) {
            const datos = JSON.parse(response);
            $('#nombre').val(datos.nombre);
            $('#usuario').val(datos.usuario);
            $('#correo').val(datos.correo);
            $('#id').val(datos.idusuario);
            $('#btnAccion').val('Modificar');
        },
        error: function (error) {
            console.log(error);

        }
    });
}

function editarProducto(id) {
    const action = "editarProducto";
    $.ajax({
        url: 'ajax.php',
        type: 'GET',
        async: true,
        data: {
            editarProducto: action,
            id: id
        },
        success: function (response) {
            const datos = JSON.parse(response);
            $('#codigo').val(datos.codigo);
            $('#producto').val(datos.descripcion);
            $('#precio').val(datos.precio);
            $('#cantidad').val(datos.cantidad);
            $('#stock_minimo').val(datos.stock_minimo || 5);
            $('#id').val(datos.codproducto);
            $('#btnAccion').val('Modificar');
        },
        error: function (error) {
            console.log(error);

        }
    });
}

function limpiar() {
    $('#formulario')[0].reset();
    $('#id').val('');
    $('#btnAccion').val('Registrar');
}

// ============================================
// CALCULAR VUELTO AUTOMÁTICAMENTE
// ============================================
$(document).ready(function() {
    // Calcular vuelto cuando cambia el monto pagado
    $('#monto_pagado').on('input', function() {
        formatearInputMoneda(this);
        calcularVuelto();
    });
    
    // Formatear al cargar
    $('#monto_pagado').trigger('input');
    
    // Event listener para el descuento global
    $('#descuento_global').on('input change', function() {
        var valorInput = $(this).val();
        if (valorInput !== '') {
            var val = parseFloat(valorInput);
            if (val < 0) $(this).val('');
            if (val > 100) $(this).val(100);
        }
        calcularTotalConDescuento();
    });
    
    // También recalcular cuando cambia el total de la venta
    var observerVuelto = new MutationObserver(function() {
        calcularVuelto();
    });
    
    var totalElement = document.querySelector('#tblDetalle tfoot tr td:nth-child(2)');
    if (totalElement) {
        observerVuelto.observe(totalElement, { childList: true, characterData: true, subtree: true });
    }
});

function calcularVuelto() {
    var monto_pagado = limpiarFormato($('#monto_pagado').val());
    
    // Usar el total con descuento en lugar del total sin descuento
    var totalConDescuentoText = $('#total_con_descuento').val();
    var total = 0;
    
    if (totalConDescuentoText) {
        total = limpiarFormato(totalConDescuentoText);
    }
    
    var vuelto = monto_pagado - total;
    
    // Actualizar el campo de vuelto con formato
    if (vuelto > 0) {
        $('#vuelto').val(formatearMoneda(vuelto));
        $('#vuelto').removeClass('text-danger').addClass('text-success');
    } else if (vuelto < 0) {
        $('#vuelto').val('-' + formatearMoneda(Math.abs(vuelto)));
        $('#vuelto').removeClass('text-success').addClass('text-danger');
    } else {
        $('#vuelto').val(formatearMoneda(0));
        $('#vuelto').removeClass('text-danger text-success');
    }
}