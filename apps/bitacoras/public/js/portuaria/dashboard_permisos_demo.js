// Simula: SELECT TOP 1000 [idusuario], [menu_tram], [opcion], [item], [estado] FROM [APM].[dbo].[per_menuopciones]
const perMenuOpciones = [
  { idusuario: 1, menu_tram: "GENERAL", opcion: "DASHBOARD", item: "Dashboard", estado: 1 },
  { idusuario: 1, menu_tram: "GENERAL", opcion: "REPORTE_SUPERVISOR", item: "Reporte Supervisor", estado: 1 },
  { idusuario: 1, menu_tram: "GENERAL", opcion: "IMPORTAR_FUNCIONARIOS", item: "Importar Funcionarios", estado: 1 },
  { idusuario: 1, menu_tram: "EDIFICIO_ADMINISTRATIVO", opcion: "REGISTRAR_INGRESO", item: "Registrar ingreso", estado: 1 },
  { idusuario: 1, menu_tram: "EDIFICIO_ADMINISTRATIVO", opcion: "LISTADO_VISITAS", item: "Listado de visitas", estado: 1 },
  { idusuario: 1, menu_tram: "EDIFICIO_ADMINISTRATIVO", opcion: "REGISTROS_BASE", item: "Registros Base", estado: 1 },
  { idusuario: 1, menu_tram: "EDIFICIO_ADMINISTRATIVO", opcion: "DESACTIVADA", item: "Opción desactivada", estado: 0 },

  { idusuario: 2, menu_tram: "GENERAL", opcion: "DASHBOARD", item: "Dashboard", estado: 1 },
  { idusuario: 2, menu_tram: "GENERAL", opcion: "REPORTE_SUPERVISOR", item: "Reporte Supervisor", estado: 1 },
  { idusuario: 2, menu_tram: "GENERAL", opcion: "IMPORTAR_FUNCIONARIOS", item: "Importar Funcionarios", estado: 1 },
  { idusuario: 3, menu_tram: "GENERAL", opcion: "DASHBOARD", item: "Dashboard", estado: 1 },
  { idusuario: 3, menu_tram: "GENERAL", opcion: "REPORTE_SUPERVISOR", item: "Reporte Supervisor", estado: 1 },
  { idusuario: 3, menu_tram: "GENERAL", opcion: "IMPORTAR_FUNCIONARIOS", item: "Importar Funcionarios", estado: 1 },
  { idusuario: 4, menu_tram: "GENERAL", opcion: "DASHBOARD", item: "Dashboard", estado: 1 },
  { idusuario: 4, menu_tram: "GENERAL", opcion: "REPORTE_SUPERVISOR", item: "Reporte Supervisor", estado: 1 },
  { idusuario: 4, menu_tram: "GENERAL", opcion: "IMPORTAR_FUNCIONARIOS", item: "Importar Funcionarios", estado: 1 },
  { idusuario: 5, menu_tram: "GENERAL", opcion: "DASHBOARD", item: "Dashboard", estado: 1 },
  { idusuario: 5, menu_tram: "GENERAL", opcion: "REPORTE_SUPERVISOR", item: "Reporte Supervisor", estado: 1 },
  { idusuario: 5, menu_tram: "GENERAL", opcion: "IMPORTAR_FUNCIONARIOS", item: "Importar Funcionarios", estado: 1 },
  { idusuario: 6, menu_tram: "GENERAL", opcion: "DASHBOARD", item: "Dashboard", estado: 1 },
  { idusuario: 6, menu_tram: "GENERAL", opcion: "REPORTE_SUPERVISOR", item: "Reporte Supervisor", estado: 1 },
  { idusuario: 6, menu_tram: "GENERAL", opcion: "IMPORTAR_FUNCIONARIOS", item: "Importar Funcionarios", estado: 1 },
  { idusuario: 7, menu_tram: "GENERAL", opcion: "DASHBOARD", item: "Dashboard", estado: 1 },
  { idusuario: 7, menu_tram: "GENERAL", opcion: "REPORTE_SUPERVISOR", item: "Reporte Supervisor", estado: 1 },
  { idusuario: 7, menu_tram: "GENERAL", opcion: "IMPORTAR_FUNCIONARIOS", item: "Importar Funcionarios", estado: 1 },
  { idusuario: 8, menu_tram: "GENERAL", opcion: "DASHBOARD", item: "Dashboard", estado: 1 },
  { idusuario: 8, menu_tram: "GENERAL", opcion: "REPORTE_SUPERVISOR", item: "Reporte Supervisor", estado: 1 },
  { idusuario: 8, menu_tram: "GENERAL", opcion: "IMPORTAR_FUNCIONARIOS", item: "Importar Funcionarios", estado: 1 }
];

function getMenuByUserId(id) {
  const activos = perMenuOpciones.filter((r) => Number(r.idusuario) === Number(id) && Number(r.estado) === 1);
  const general = activos.filter((r) => r.menu_tram === "GENERAL");
  const admin = activos.filter((r) => r.menu_tram === "EDIFICIO_ADMINISTRATIVO");
  return {
    general: general,
    adminGroup: admin.length > 0 ? { title: "Edificio Administrativo", children: admin } : null
  };
}

function renderSidebar(menu) {
  const ul = document.getElementById("menuGlobal");
  ul.innerHTML = "";
  menu.general.forEach((it) => {
    const li = document.createElement("li");
    li.className = "nav-item";
    li.innerHTML = '<a class="nav-link text-white" href="#"><i class="bi bi-dot"></i> ' + it.item + "</a>";
    ul.appendChild(li);
  });

  const adminWrap = document.getElementById("adminWrap");
  const adminList = document.getElementById("menuAdmin");
  adminList.innerHTML = "";
  if (!menu.adminGroup) {
    adminWrap.style.display = "none";
    return;
  }
  adminWrap.style.display = "block";
  menu.adminGroup.children.forEach((it) => {
    const li = document.createElement("li");
    li.className = "nav-item";
    li.innerHTML = '<a class="nav-link text-white-50 ps-4" href="#"><i class="bi bi-caret-right-fill"></i> ' + it.item + "</a>";
    adminList.appendChild(li);
  });
}

function renderHeader(id) {
  const dept = document.getElementById("deptText");
  dept.textContent = id === 1 ? "Edificio Administrativo" : "Departamento: " + id;
}

function renderTabla(id) {
  const rows = perMenuOpciones.filter((r) => Number(r.idusuario) === Number(id));
  const tbody = document.getElementById("tablaPermisosBody");
  tbody.innerHTML = rows
    .map((r) => {
      return (
        "<tr>" +
        "<td>" + r.idusuario + "</td>" +
        "<td>" + r.menu_tram + "</td>" +
        "<td>" + r.opcion + "</td>" +
        "<td>" + r.item + "</td>" +
        "<td>" + r.estado + "</td>" +
        "</tr>"
      );
    })
    .join("");

  if ($.fn.DataTable.isDataTable("#tablaPermisos")) {
    $("#tablaPermisos").DataTable().destroy();
  }
  $("#tablaPermisos").DataTable({
    pageLength: 5,
    lengthMenu: [[5, 10, 25], [5, 10, 25]],
    language: {
      search: "Buscar:",
      lengthMenu: "Mostrar _MENU_ registros",
      info: "Mostrando _START_ a _END_ de _TOTAL_",
      infoEmpty: "Sin registros",
      infoFiltered: "(filtrado de _MAX_)",
      paginate: { first: "Primera", last: "Última", next: "Siguiente", previous: "Anterior" },
      zeroRecords: "No hay coincidencias"
    }
  });
}

async function pedirLogin() {
  const result = await Swal.fire({
    title: "Login de prueba",
    text: "Ingrese ID de Departamento (1-8)",
    input: "number",
    inputAttributes: { min: 1, max: 8, step: 1 },
    allowOutsideClick: false,
    confirmButtonText: "Ingresar",
    inputValidator: (value) => {
      const id = Number(value);
      if (!Number.isInteger(id) || id < 1 || id > 8) {
        return "ID no reconocido";
      }
      return null;
    }
  });

  if (!result.isConfirmed) return null;
  return Number(result.value);
}

document.addEventListener("DOMContentLoaded", async function () {
  const id = await pedirLogin();
  if (!id) return;

  Swal.fire({
    icon: "success",
    title: "Acceso concedido",
    text: "Bienvenido, ID " + id,
    timer: 1000,
    showConfirmButton: false
  });

  const menu = getMenuByUserId(id);
  renderHeader(id);
  renderSidebar(menu);
  renderTabla(id);

  const adminToggle = document.getElementById("adminToggle");
  adminToggle.addEventListener("click", function () {
    $("#menuAdmin").slideToggle(120);
    const icon = document.getElementById("adminArrow");
    icon.classList.toggle("bi-chevron-down");
    icon.classList.toggle("bi-chevron-right");
  });
});

