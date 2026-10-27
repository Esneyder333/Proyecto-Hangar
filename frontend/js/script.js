
const URL_PRODUCTOS = "../api/productos.php";
const URL_VENTAS = "../api/ventas.php";
const WHATSAPP = "573118098212";

let productos = [];
let cantidades = {};

function precioCOP(valor) {
    return "$" + Number(valor).toLocaleString("es-CO");
}

async function cargarProductos() {
    const lista = document.getElementById("lista-productos");
    const mensaje = document.getElementById("mensaje");

    try {
        const respuesta = await fetch(URL_PRODUCTOS);
        const datos = await respuesta.json();

        if (!respuesta.ok || !Array.isArray(datos)) {
            throw new Error("No se pudo cargar el catálogo.");
        }

        productos = datos;
        lista.innerHTML = "";
        cantidades = {};

        productos.forEach(function(producto) {
            cantidades[producto.id] = 0;
            lista.appendChild(crearTarjeta(producto));
        });

        mensaje.textContent =
            productos.length + " suministros disponibles.";

        actualizarTotales();

    } catch (error) {
        console.error(error);
        mensaje.textContent =
            "Error al cargar el catálogo. Revisa XAMPP y la API.";
    }
}

function crearTarjeta(producto) {
    const tarjeta = document.createElement("article");
    tarjeta.className = "cerveza-card";

    const cuadroImagen = document.createElement("div");
    cuadroImagen.className = "cerveza-imagen";

    const imagen = document.createElement("img");
    imagen.alt = producto.nombre;

    const placeholder = document.createElement("div");
    placeholder.className = "imagen-placeholder";
    placeholder.innerHTML =
        "<span>🍺</span><small>Imagen no disponible</small>";

    if (producto.imagen) {
        imagen.src = "img/" + producto.imagen;
    } else {
        imagen.style.display = "none";
    }

    imagen.onerror = function() {
        imagen.style.display = "none";
        placeholder.style.display = "flex";
    };

    imagen.onload = function() {
        placeholder.style.display = "none";
        imagen.style.display = "block";
    };

    cuadroImagen.append(imagen, placeholder);

    const categoria = document.createElement("span");
    categoria.className = "cerveza-categoria";
    categoria.textContent = producto.categoria || "Cerveza";

    const nombre = document.createElement("h3");
    nombre.textContent = producto.nombre;

    const precio = document.createElement("p");
    precio.className = "cerveza-precio";
    precio.textContent = precioCOP(producto.precio);

    const alertaStock = document.createElement("p");
    const stock = Number(producto.stock);

    if (stock <= 0) {
        alertaStock.className = "alerta-stock";
        alertaStock.textContent = "⚠ PRODUCTO AGOTADO";
    } else if (stock <= 5) {
        alertaStock.className = "alerta-stock";
        alertaStock.textContent = "⚠ STOCK BAJO: quedan " + stock;
    } else {
        alertaStock.className = "stock-normal";
        alertaStock.textContent = "Disponibles: " + stock;
    }

    const control = document.createElement("div");
    control.className = "control-cantidad";

    const menos = document.createElement("button");
    menos.type = "button";
    menos.textContent = "−";
    menos.setAttribute("aria-label", "Restar " + producto.nombre);

    const cantidad = document.createElement("output");
    cantidad.textContent = "0";

    const mas = document.createElement("button");
    mas.type = "button";
    mas.textContent = "+";
    mas.setAttribute("aria-label", "Sumar " + producto.nombre);

    if (stock <= 0) {
        mas.disabled = true;
        menos.disabled = true;
    }

    menos.addEventListener("click", function() {
        cambiarCantidad(producto, -1, cantidad);
    });

    mas.addEventListener("click", function() {
        cambiarCantidad(producto, 1, cantidad);
    });

    control.append(menos, cantidad, mas);

    tarjeta.append(
        cuadroImagen,
        categoria,
        nombre,
        precio,
        alertaStock,
        control
    );

    return tarjeta;
}

function cambiarCantidad(producto, cambio, elemento) {
    const id = producto.id;
    const actual = Number(cantidades[id]) || 0;
    const nueva = actual + cambio;

    if (nueva < 0) return;

    if (nueva > Number(producto.stock)) {
        alert("No hay suficientes unidades disponibles.");
        return;
    }

    cantidades[id] = nueva;
    elemento.textContent = nueva;

    actualizarTotales();
}

function obtenerSeleccionados() {
    return productos.filter(function(producto) {
        return Number(cantidades[producto.id]) > 0;
    });
}

function calcularTotal() {
    return obtenerSeleccionados().reduce(function(total, producto) {
        return total +
            Number(producto.precio) * Number(cantidades[producto.id]);
    }, 0);
}

function actualizarTotales() {
    const total = calcularTotal();

    document.getElementById("total-catalogo").textContent =
        precioCOP(total);

    document.getElementById("total-ticket").textContent =
        precioCOP(total);

    const unidades = obtenerSeleccionados().reduce(function(suma, producto) {
        return suma + Number(cantidades[producto.id]);
    }, 0);

    document.getElementById("cantidad-articulos").textContent =
        unidades + (unidades === 1
            ? " artículo seleccionado"
            : " artículos seleccionados");

    actualizarDetalleTicket();
}

function actualizarDetalleTicket() {
    const detalle = document.getElementById("detalle-ticket");
    detalle.innerHTML = "";

    const seleccionados = obtenerSeleccionados();

    seleccionados.forEach(function(producto) {
        const cantidad = Number(cantidades[producto.id]);

        const fila = document.createElement("div");
        fila.className = "ticket-fila";

        const informacion = document.createElement("div");

        const nombre = document.createElement("p");
        nombre.className = "ticket-nombre";
        nombre.textContent = producto.nombre;

        const unidades = document.createElement("p");
        unidades.className = "ticket-detalle";
        unidades.textContent =
            cantidad + " × " + precioCOP(producto.precio);

        const subtotal = document.createElement("strong");
        subtotal.className = "ticket-subtotal";
        subtotal.textContent =
            precioCOP(cantidad * Number(producto.precio));

        informacion.append(nombre, unidades);
        fila.append(informacion, subtotal);
        detalle.appendChild(fila);
    });

    if (seleccionados.length === 0) {
        const vacio = document.createElement("p");
        vacio.className = "ticket-vacio";
        vacio.textContent = "Aún no has seleccionado productos.";
        detalle.appendChild(vacio);
    }
}

function mostrarTicket() {
    if (obtenerSeleccionados().length === 0) {
        alert("Selecciona al menos una cerveza antes de pedir.");
        return;
    }

    const ticket = document.getElementById("ticket-pedido");
    ticket.hidden = false;

    ticket.scrollIntoView({
        behavior: "smooth",
        block: "start"
    });
}

async function finalizarPedido() {
    const seleccionados = obtenerSeleccionados();

    if (seleccionados.length === 0) {
        alert("Selecciona al menos una cerveza.");
        return;
    }

    const boton = document.getElementById("finalizar-pedido");
    boton.disabled = true;

    try {
        const pedido = {
            productos: seleccionados.map(function(producto) {
                return {
                    producto_id: Number(producto.id),
                    cantidad: Number(cantidades[producto.id])
                };
            })
        };

        const respuesta = await fetch(URL_VENTAS, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(pedido)
        });

        const resultado = await respuesta.json();

        if (!respuesta.ok) {
            throw new Error(
                resultado.error || "No se pudo registrar la venta."
            );
        }

        const medioPago = document.getElementById("medio-pago").value;

        let mensaje = "PEDIDO - EL HANGAR\n";
        mensaje += "Venta registrada: #" + resultado.venta_id + "\n";
        mensaje += "Donde despega el sabor\n\n";
        mensaje += "DETALLE DEL PEDIDO:\n";

        seleccionados.forEach(function(producto) {
            const cantidad = Number(cantidades[producto.id]);

            mensaje += "- " + producto.nombre +
                " | Cantidad: " + cantidad +
                " | Subtotal: " +
                precioCOP(cantidad * Number(producto.precio)) + "\n";
        });

        mensaje += "\nTOTAL: " + precioCOP(resultado.total);
        mensaje += "\nMEDIO DE PAGO: " + medioPago;
        mensaje += "\n\nHola, quiero confirmar este pedido.";

        alert(
            "Venta #" + resultado.venta_id +
            " registrada correctamente.\nTotal: " +
            precioCOP(resultado.total)
        );

        window.location.href =
            "https://wa.me/" + WHATSAPP +
            "?text=" + encodeURIComponent(mensaje);

    } catch (error) {
        alert(error.message);
    } finally {
        boton.disabled = false;
    }
}

document.addEventListener("DOMContentLoaded", function() {
    cargarProductos();

    document.getElementById("pedir-ahora")
        .addEventListener("click", mostrarTicket);

    document.getElementById("finalizar-pedido")
        .addEventListener("click", finalizarPedido);
});
