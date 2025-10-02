<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Barra superior -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="flex justify-between items-center px-4 py-3">
        <div class="flex items-center">
          <button
            @click="alternarMenuLateral"
            class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
          >
            <i class="fas fa-bars"></i>
          </button>
          <h1 class="ml-2 text-xl font-semibold text-gray-900">Sistema de Reportes TURF</h1>
        </div>
        
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-700">
            {{ authStore.nombreUsuario }} ({{ authStore.usuario?.role }})
          </span>
          <button
            @click="authStore.cerrarSesion"
            class="text-sm text-gray-500 hover:text-gray-700"
          >
            <i class="fas fa-sign-out-alt mr-1"></i>
            Cerrar Sesión
          </button>
        </div>
      </div>
    </header>

    <div class="flex">
      <!-- Menú lateral -->
      <aside
        :class="[
          'bg-white shadow-sm border-r border-gray-200 transition-transform duration-300 ease-in-out',
          'md:translate-x-0 md:static md:inset-0',
          menuVisible ? 'translate-x-0' : '-translate-x-full',
          'fixed inset-y-0 left-0 z-50 w-80 md:w-80 lg:w-96 overflow-y-auto'
        ]"
      >
        <nav class="mt-5 px-2">
          <ul class="space-y-1">
            <!-- Dashboard siempre visible -->
            <li>
              <a
                href="#"
                @click.prevent="seleccionarMenu('dashboard')"
                :class="[
                  'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors',
                  reportesStore.reporteActual === 'dashboard' 
                    ? 'bg-blue-100 text-blue-700' 
                    : 'text-gray-700 hover:bg-gray-100'
                ]"
              >
                <i class="fas fa-tachometer-alt mr-3"></i>
                Dashboard
              </a>
            </li>

            <!-- Menús según rol (comentado temporalmente) -->
            <!-- 
            <li v-for="menu in menusDisponibles" :key="menu.id">
              <a
                href="#"
                @click.prevent="seleccionarMenu(menu.id)"
                :class="[
                  'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors',
                  reportesStore.reporteActual === menu.id 
                    ? 'bg-blue-100 text-blue-700' 
                    : 'text-gray-700 hover:bg-gray-100'
                ]"
              >
                <i :class="menu.icon + ' mr-3'"></i>
                {{ menu.title }}
              </a>
            </li>
            -->
          </ul>
        </nav>

        <!-- Menú de reportes -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
          <h2 class="text-xl font-semibold mb-4">Reportes Disponibles</h2>
          
          <!-- Sección Agencia -->
          <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-700 mb-3 flex items-center">
              <i class="fas fa-building mr-2"></i>
              Agencia
            </h3>
            <div class="space-y-2">
              <button
                v-for="reporte in reportesAgencia"
                :key="reporte.id"
                @click="seleccionarReporte(reporte.id, 'agencia')"
                :class="[
                  'w-full p-4 rounded-lg border-2 transition-colors text-left flex items-center',
                  reportesStore.reporteActual === reporte.id && reportesStore.origenActual === 'agencia'
                    ? 'border-blue-500 bg-blue-50 text-blue-700'
                    : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                ]"
              >
                <i :class="reporte.icono" class="text-xl mr-3"></i>
                <span class="font-medium">{{ reporte.nombre }}</span>
              </button>

              <!-- Menú Desplegable: Informe (solo Admin) -->
              <div v-if="authStore.esAdmin" class="border-2 border-gray-200 rounded-lg">
                <button
                  @click="toggleInformeMenu"
                  :class="[
                    'w-full p-4 rounded-lg transition-colors text-left flex items-center justify-between',
                    menuInformeAbierto || reportesStore.reporteActual === 'informe-parte-venta'
                      ? 'bg-blue-50 text-blue-700'
                      : 'hover:bg-gray-50'
                  ]"
                >
                  <div class="flex items-center">
                    <i class="fas fa-file-invoice text-xl mr-3"></i>
                    <span class="font-medium">Informe</span>
                  </div>
                  <i :class="menuInformeAbierto ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="text-sm"></i>
                </button>
                
                <!-- Sub-reportes -->
                <div v-show="menuInformeAbierto" class="bg-gray-50 px-4 pb-2">
                  <button
                    @click="seleccionarReporte('informe-parte-venta', 'agencia')"
                    :class="[
                      'w-full p-3 mt-2 rounded-lg border transition-colors text-left flex items-center',
                      reportesStore.reporteActual === 'informe-parte-venta'
                        ? 'border-blue-500 bg-white text-blue-700 font-medium'
                        : 'border-gray-200 bg-white hover:border-blue-300'
                    ]"
                  >
                    <i class="fas fa-chart-pie text-lg mr-3"></i>
                    <span>Parte de Venta</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Sección AppWeb (solo para admin) -->
          <div v-if="authStore.esAdmin" class="mb-6">
            <h3 class="text-lg font-medium text-gray-700 mb-3 flex items-center">
              <i class="fas fa-globe-americas mr-2"></i>
              AppWeb
            </h3>
            <div class="space-y-2">
              <button
                v-for="reporte in reportesAppWeb"
                :key="reporte.id"
                @click="seleccionarReporte(reporte.id, 'appweb')"
                :class="[
                  'w-full p-4 rounded-lg border-2 transition-colors text-left flex items-center',
                  reportesStore.reporteActual === reporte.id && reportesStore.origenActual === 'appweb'
                    ? 'border-green-500 bg-green-50 text-green-700'
                    : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                ]"
              >
                <i :class="reporte.icono" class="text-xl mr-3"></i>
                <span class="font-medium">{{ reporte.nombre }}</span>
              </button>
            </div>
          </div>
        </div>
      </aside>

      <!-- Overlay para móvil -->
      <div
        v-if="menuVisible"
        @click="alternarMenuLateral"
        class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 md:hidden"
      ></div>

      <!-- Contenido principal -->
      <main class="flex-1 p-6">
        <!-- Dashboard -->
        <div v-if="reportesStore.reporteActual === 'dashboard'">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">Dashboard</h2>
          <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600">
              Bienvenido al sistema de reportes TURF. Selecciona un reporte del menú lateral para comenzar.
            </p>
          </div>
        </div>

        <!-- Área de reportes -->
        <div v-else>
          <!-- Filtros -->
          <div class="bg-white rounded-lg shadow mb-6 p-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Filtros</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde</label>
                <input
                  v-model="reportesStore.filtros.fechaDesde"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta</label>
                <input
                  v-model="reportesStore.filtros.fechaHasta"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div v-if="authStore.esAdmin && mostrarFiltroAgencia">
                <label class="block text-sm font-medium text-gray-700 mb-1">Agencia</label>
                <select
                  v-model="reportesStore.filtros.agenciaId"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Todas las agencias</option>
                  <option v-for="agencia in agencias" :key="agencia.id_agencia" :value="agencia.id_agencia">
                    {{ agencia.nombre_agencia }}
                  </option>
                </select>
              </div>
              <!-- Filtro de búsqueda por usuario para reportes AppWeb -->
              <div v-if="reportesStore.origenActual === 'appweb' && reportesStore.reporteActual === 'por-usuario'">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar Usuario</label>
                <input
                  v-model="reportesStore.filtros.buscarUsuario"
                  type="text"
                  placeholder="Nombre o apellido..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div class="flex items-end space-x-2">
                <button
                  @click="aplicarFiltros"
                  :disabled="reportesStore.cargando"
                  class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                >
                  <i :class="reportesStore.cargando ? 'fas fa-spinner fa-spin' : 'fas fa-search'" class="mr-2"></i>
                  {{ reportesStore.cargando ? 'Cargando...' : 'Aplicar Filtros' }}
                </button>
                <!-- Exportación (solo rol Agencia y reportes permitidos) -->
                <div v-if="authStore.esAgencia && exportDisponible" class="flex items-center space-x-2">
                  <select v-model="formatoExport" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="csv">CSV</option>
                    <option value="pdf">PDF</option>
                    <option value="xlsx">Excel</option>
                  </select>
                  <button
                    @click="exportar(formatoExport)"
                    :disabled="exportando"
                    class="px-4 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                    title="Exportar reporte"
                  >
                    <i :class="exportando ? 'fas fa-spinner fa-spin' : 'fas fa-download'" class="mr-2"></i>
                    Exportar
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Encabezado del reporte con origen (Movido aquí) -->
          <div v-if="reportesStore.reporteActual && reportesStore.reporteActual !== 'dashboard'" 
               class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <!-- Icono del origen -->
                <div v-if="infoReporteActual.iconoOrigen" 
                     :class="[
                       'p-3 rounded-lg',
                       infoReporteActual.origen === 'Agencia' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600'
                     ]">
                  <i :class="infoReporteActual.iconoOrigen + ' text-xl'"></i>
                </div>
                
                <!-- Información del reporte -->
                <div>
                  <div class="flex items-center space-x-2">
                    <span :class="[
                      'px-2 py-1 text-xs font-medium rounded-full',
                      infoReporteActual.origen === 'Agencia' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'
                    ]">
                      {{ infoReporteActual.origen }}
                    </span>
                  </div>
                  <h2 class="text-xl font-semibold text-gray-900 mt-1 flex items-center">
                    <i :class="infoReporteActual.icono + ' mr-2'"></i>
                    {{ infoReporteActual.nombre }}
                  </h2>
                </div>
              </div>
              <!-- Acciones de exportación (solo Agencia) -->
              <div v-if="authStore.esAgencia && exportDisponible" class="flex items-center space-x-2">
                <button
                  @click="exportar('csv')"
                  :disabled="exportando"
                  class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                  title="Exportar a CSV"
                >
                  <i :class="exportando ? 'fas fa-spinner fa-spin' : 'fas fa-file-csv'" class="mr-2"></i>
                  CSV
                </button>
                <button
                  @click="exportar('pdf')"
                  :disabled="exportando"
                  class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                  title="Exportar a PDF"
                >
                  <i :class="exportando ? 'fas fa-spinner fa-spin' : 'fas fa-file-pdf'" class="mr-2"></i>
                  PDF
                </button>
              </div>
            </div>
          </div>

          <!-- KPIs específicos para reporte Por Usuario de AppWeb -->
          <div v-if="reportesStore.origenActual === 'appweb' && reportesStore.reporteActual === 'por-usuario' && reportesStore.kpis && Object.keys(reportesStore.kpis).length > 0"
               class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <KPICard 
              titulo="Total Usuarios"
              :valor="reportesStore.kpis.total_usuarios"
              tipo="numero"
              icono="fas fa-users"
              :color="obtenerColorKPI('total_usuarios')"
            />
            <KPICard 
              titulo="Total Apostado"
              :valor="reportesStore.kpis.total_apostado"
              tipo="moneda"
              icono="fas fa-coins"
              :color="obtenerColorKPI('total_apostado')"
            />
            <KPICard 
              titulo="Total Premios"
              :valor="reportesStore.kpis.total_premios"
              tipo="moneda"
              icono="fas fa-trophy"
              :color="obtenerColorKPI('total_premios')"
            />
            <KPICard 
              titulo="Ganancia Casa"
              :valor="reportesStore.kpis.ganancia_casa"
              :tipo="parseFloat(reportesStore.kpis.ganancia_casa) >= 0 ? 'ganancia' : 'perdida'"
              icono="fas fa-chart-line"
              :color="obtenerColorKPI('ganancia_casa')"
            />
          </div>

          <!-- KPIs específicos para reporte Económico de AppWeb -->
          <div v-if="reportesStore.origenActual === 'appweb' && reportesStore.reporteActual === 'economico' && reportesStore.kpis && Object.keys(reportesStore.kpis).length > 0"
               class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <KPICard 
              titulo="Total Acreditado"
              :valor="reportesStore.kpis.total_acreditado"
              tipo="moneda"
              icono="fas fa-arrow-up"
              :color="obtenerColorKPI('total_acreditado')"
            />
            <KPICard 
              titulo="Total Debitado"
              :valor="reportesStore.kpis.total_debitado"
              tipo="moneda"
              icono="fas fa-arrow-down"
              :color="obtenerColorKPI('total_debitado')"
            />
            <KPICard 
              titulo="Diferencia"
              :valor="reportesStore.kpis.diferencia"
              :tipo="parseFloat(reportesStore.kpis.diferencia) >= 0 ? 'ganancia' : 'perdida'"
              icono="fas fa-balance-scale"
              :color="obtenerColorKPI('diferencia')"
            />
          </div>

          <!-- KPI específico para reporte Dinero Remanente de AppWeb -->
          <div v-if="reportesStore.origenActual === 'appweb' && reportesStore.reporteActual === 'dinero-remanente' && reportesStore.kpis && Object.keys(reportesStore.kpis).length > 0"
               class="flex justify-center mb-8">
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white p-8 rounded-lg shadow-lg text-center max-w-md">
              <h3 class="text-lg font-semibold mb-2">TOTAL DINERO REMANENTE</h3>
              <p class="text-4xl font-bold">
                {{ formatearMoneda(reportesStore.kpis.total_dinero_remanente) }}
              </p>
              <p class="text-sm mt-2 opacity-90">Pasivo total hacia usuarios</p>
            </div>
          </div>

          <!-- KPIs específicos para reporte Apuestas de AppWeb -->
          <div v-if="reportesStore.origenActual === 'appweb' && reportesStore.reporteActual === 'apuestas' && reportesStore.kpis && Object.keys(reportesStore.kpis).length > 0"
               class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <KPICard 
              titulo="Total Ingreso por Apuestas"
              :valor="reportesStore.kpis.total_ingreso_apuestas"
              tipo="moneda"
              icono="fas fa-coins"
              :color="obtenerColorKPI('total_ingreso_por_apuestas')"
            />
            <KPICard 
              titulo="Total Premios Pagados"
              :valor="reportesStore.kpis.total_premios_pagados"
              tipo="moneda"
              icono="fas fa-trophy"
              :color="obtenerColorKPI('total_premios_pagados')"
            />
            <KPICard 
              titulo="Diferencia (Ganancia Casa)"
              :valor="reportesStore.kpis.diferencia"
              :tipo="parseFloat(reportesStore.kpis.diferencia) >= 0 ? 'ganancia' : 'perdida'"
              icono="fas fa-chart-line"
              :color="obtenerColorKPI('diferencia_(ganancia_casa)')"
            />
          </div>

          <!-- KPIs Cards especiales para Ventas Diarias del rol agencia -->
          <div v-if="reportesStore.reporteActual === 'ventas-diarias' && !authStore.esAdmin" 
               class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            
            <!-- Total Vendido -->
            <div class="bg-white p-6 rounded-lg shadow-md">
              <p class="text-sm text-gray-600">Total Vendido</p>
              <p class="text-2xl font-bold text-green-600">
                {{ formatearMoneda(reportesStore.kpis?.total_vendidos || 0) }}
              </p>
            </div>
            
            <!-- Total Ganadores -->
            <div class="bg-white p-6 rounded-lg shadow-md">
              <p class="text-sm text-gray-600">Total Ganadores</p>
              <p class="text-2xl font-bold text-blue-600">
                {{ formatearMoneda(reportesStore.kpis?.total_ganadores || 0) }}
              </p>
            </div>
            
            <!-- Total Pagados -->
            <div class="bg-white p-6 rounded-lg shadow-md">
              <p class="text-sm text-gray-600">Total Pagados</p>
              <p class="text-2xl font-bold text-yellow-600">
                {{ formatearMoneda(reportesStore.kpis?.total_pagados || 0) }}
              </p>
            </div>
            
            <!-- Total Devoluciones -->
            <div class="bg-white p-6 rounded-lg shadow-md">
              <p class="text-sm text-gray-600">Total Devoluciones</p>
              <p class="text-2xl font-bold text-red-600">
                {{ formatearMoneda(reportesStore.kpis?.total_devoluciones || 0) }}
              </p>
            </div>
            
            <!-- Ganancia -->
            <div class="bg-white p-6 rounded-lg shadow-md">
              <p class="text-sm text-gray-600">Ganancia</p>
              <p class="text-2xl font-bold text-purple-600">
                {{ formatearMoneda(calcularGanancia()) }}
              </p>
            </div>
          </div>

          <!-- KPIs Cards especiales para Caballos Retirados -->
          <div v-if="reportesStore.reporteActual === 'caballos-retirados'" 
               class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total General a Devolver -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-6">
              <div class="text-center">
                <h3 class="text-sm font-medium text-red-600 mb-2">Total General a Devolver</h3>
                <p class="text-2xl font-bold text-red-700">
                  {{ formatearMoneda(reportesStore.kpis?.total_general_devolver || 0) }}
                </p>
              </div>
            </div>
            
            <!-- Total Devuelto -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
              <div class="text-center">
                <h3 class="text-sm font-medium text-green-600 mb-2">Total Devuelto</h3>
                <p class="text-2xl font-bold text-green-700">
                  {{ formatearMoneda(reportesStore.kpis?.total_devuelto || 0) }}
                </p>
              </div>
            </div>
            
            <!-- Total General de Apuestas -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
              <div class="text-center">
                <h3 class="text-sm font-medium text-blue-600 mb-2">Total General de Apuestas</h3>
                <p class="text-2xl font-bold text-blue-700">
                  {{ formatearMoneda(reportesStore.kpis?.total_general_apuestas || 0) }}
                </p>
              </div>
            </div>
          </div>

          <!-- KPIs Cards normales para otros reportes -->
          <div v-else-if="reportesStore.kpis && Object.keys(reportesStore.kpis).length > 0 && 
                          reportesStore.reporteActual !== 'informe-agencias' && 
                          !(reportesStore.reporteActual === 'ventas-diarias' && !authStore.esAdmin) && 
                          !(reportesStore.origenActual === 'appweb' && ['por-usuario', 'economico', 'dinero-remanente', 'apuestas'].includes(reportesStore.reporteActual))" 
               class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <KPICard 
              v-for="(valor, clave) in kpisFiltrados" 
              :key="clave"
              :titulo="formatearTituloKPI(clave)"
              :valor="valor"
              :tipo="obtenerTipoKPI(clave)"
              :icono="obtenerIconoKPI(clave)"
              :color="obtenerColorKPI(clave)"
            />
          </div>

          <!-- Tabla especial para Informe - Parte de Venta -->
          <TablaInformeParteVenta
            v-if="reportesStore.reporteActual === 'informe-parte-venta'"
            :kpis="reportesStore.kpis"
            :cargando="reportesStore.cargando"
            :error="reportesStore.error"
          />

          <!-- Tabla especial para reporte Por Usuario de AppWeb -->
          <div v-if="reportesStore.origenActual === 'appweb' && reportesStore.reporteActual === 'por-usuario' && reportesStore.datosReporte.length > 0"
               class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Rendimiento por Usuario</h3>
              <p class="text-sm text-gray-600">{{ reportesStore.datosReporte.length }} usuarios encontrados</p>
            </div>
            
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Crédito</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Apostado</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Premios</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Diferencia</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Jugadas</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <template v-for="usuario in reportesStore.datosReporte" :key="usuario.id_usuario">
                    <!-- Fila principal del usuario -->
                    <tr class="hover:bg-gray-50">
                      <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div>
                          <div class="text-sm font-medium text-gray-900">{{ usuario.nombre_completo }}</div>
                          <div class="text-sm text-gray-500">{{ usuario.email }}</div>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                        {{ formatearMoneda(usuario.saldo_credito) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                        {{ formatearMoneda(usuario.total_apostado) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                        {{ formatearMoneda(usuario.total_premios) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center" 
                          :class="parseFloat(usuario.diferencia) >= 0 ? 'text-green-600' : 'text-red-600'">
                        {{ formatearMoneda(usuario.diferencia) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                        {{ usuario.total_jugadas }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                        <button
                          @click="toggleDetalleUsuario(usuario.id_usuario)"
                          class="text-blue-600 hover:text-blue-900 flex items-center"
                        >
                          <i :class="usuariosExpandidos.has(usuario.id_usuario) ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="mr-1"></i>
                          {{ usuariosExpandidos.has(usuario.id_usuario) ? 'Ocultar' : 'Ver detalles' }}
                        </button>
                      </td>
                    </tr>
                    
                    <!-- Fila expandible con detalles -->
                    <tr v-if="usuariosExpandidos.has(usuario.id_usuario)" class="bg-gray-50">
                      <td colspan="7" class="px-6 py-4">
                        <div class="bg-white rounded-lg p-4 border">
                          <h4 class="text-sm font-medium text-gray-900 mb-3">Historial de Jugadas</h4>
                          
                          <!-- Loading state -->
                          <div v-if="!detallesUsuarios[usuario.id_usuario]" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin text-blue-500 mr-2"></i>
                            Cargando detalles...
                          </div>
                          
                          <!-- Tabla de detalles -->
                          <div v-else-if="detallesUsuarios[usuario.id_usuario]?.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                              <thead class="bg-gray-100">
                                <tr>
                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nro Jugada</th>
                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Carrera</th>
                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Hipódromo</th>
                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo Apuesta</th>
                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Apostado</th>
                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Premio</th>
                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Resultado</th>
                                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Caballos</th>
                                </tr>
                              </thead>
                              <tbody class="divide-y divide-gray-200">
                                <tr v-for="detalle in detallesUsuarios[usuario.id_usuario]" :key="detalle.nro_jugada" class="text-sm">
                                  <td class="px-4 py-2">{{ detalle.fecha_jugada }}</td>
                                  <td class="px-4 py-2 font-medium">{{ detalle.nro_jugada }}</td>
                                  <td class="px-4 py-2">{{ detalle.numero_carrera }}</td>
                                  <td class="px-4 py-2">{{ detalle.nombre_hipodromo }}</td>
                                  <td class="px-4 py-2">{{ detalle.tipo_apuesta }}</td>
                                  <td class="px-4 py-2">{{ formatearMoneda(detalle.monto_apostado) }}</td>
                                  <td class="px-4 py-2">{{ formatearMoneda(detalle.premio_ganado) }}</td>
                                  <td class="px-4 py-2 font-medium" :class="parseFloat(detalle.resultado) >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ formatearMoneda(detalle.resultado) }}
                                  </td>
                                  <td class="px-4 py-2">
                                    <span v-if="detalle.caballos_apostados" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                      {{ detalle.caballos_apostados }}
                                    </span>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                          
                          <!-- Sin datos -->
                          <div v-else class="text-center py-4 text-gray-500">
                            No hay jugadas registradas para este usuario
                          </div>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tabla de reportes normal para otros casos -->
          <TablaReportes 
            v-else-if="reportesStore.datosReporte.length > 0"
            :titulo="tituloReporteActual"
            :datos="reportesStore.datosReporte"
            :columnas="[]"
            :cargando="reportesStore.cargando"
            :error="reportesStore.error"
            :pagina-actual="reportesStore.paginacion.paginaActual"
            :total-paginas="reportesStore.paginacion.totalPaginas"
            :total-registros="reportesStore.paginacion.totalRegistros"
          :totales="reportesStore.reporteActual === 'informe-agencias' ? reportesStore.kpis : null"
          @cambiar-pagina="reportesStore.cambiarPagina"
          />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useReportesStore } from '../stores/reportes'
import { apiClient, reportesApi } from '../services/api'
import KPICard from './KPICard.vue'
import TablaReportes from './TablaReportes.vue'
import TablaInformeParteVenta from './TablaInformeParteVenta.vue'

// Stores
const authStore = useAuthStore()
const reportesStore = useReportesStore()

// Estado reactivo
const menuVisible = ref(false)
const menuInformeAbierto = ref(false)
const agencias = ref([])
const usuariosExpandidos = ref(new Set())
const detallesUsuarios = ref({})

// Configurar fechas por defecto al montar el componente
onMounted(async () => {
  reportesStore.configurarFechasPorDefecto()
  
  // Cargar agencias si es admin
  if (authStore.esAdmin) {
    try {
      const respuesta = await apiClient.get('/reports/agencies')
      agencias.value = respuesta.data || respuesta
    } catch (error) {
      console.error('Error cargando agencias:', error)
    }
  }

  // Abrir menú "Informe" si el reporte actual es "informe-parte-venta"
  if (reportesStore.reporteActual === 'informe-parte-venta') {
    menuInformeAbierto.value = true
  }
})

// Menús disponibles según el rol del usuario
const reportesAgencia = computed(() => {
  if (authStore.esAdmin) {
    return [
      { id: 'informe-agencias', nombre: 'Informe por Agencia', icono: 'fas fa-building' },
      { id: 'ventas-tickets', nombre: 'Ventas de Tickets', icono: 'fas fa-ticket-alt' },
      { id: 'caballos-retirados', nombre: 'Caballos Retirados', icono: 'fas fa-horse' },
      { id: 'carreras', nombre: 'Carreras', icono: 'fas fa-flag-checkered' },
      { id: 'tickets-anulados', nombre: 'Tickets Anulados', icono: 'fas fa-times-circle' }
    ]
  } else {
    return [
      { id: 'ventas-diarias', nombre: 'Ventas Diarias', icono: 'fas fa-chart-line' },
      { id: 'tickets-devoluciones', nombre: 'Tickets con Devolución', icono: 'fas fa-undo' },
      { id: 'sports-carreras', nombre: 'Sports y Carreras', icono: 'fas fa-horse-head' },
      { id: 'tickets-anulados', nombre: 'Tickets Anulados', icono: 'fas fa-ban' }
    ]
  }
})

const reportesAppWeb = computed(() => {
  return [
    { id: 'por-usuario', nombre: 'Por Usuario', icono: 'fas fa-users' },
    { id: 'economico', nombre: 'Económico', icono: 'fas fa-dollar-sign' },
    { id: 'apuestas', nombre: 'Apuestas', icono: 'fas fa-dice' },
    { id: 'dinero-remanente', nombre: 'Dinero Remanente', icono: 'fas fa-wallet' },
    { id: 'rendimiento-apuesta-carrera', nombre: 'Rendimiento Apuesta por Carrera', icono: 'fas fa-chart-bar' }
  ]
})

// Información completa del reporte actual
const infoReporteActual = computed(() => {
  if (reportesStore.reporteActual === 'dashboard') {
    return { nombre: 'Dashboard', origen: '', icono: 'fas fa-tachometer-alt', iconoOrigen: '' }
  }
  
  // Buscar en reportes de agencia
  let reporte = reportesAgencia.value.find(r => r.id === reportesStore.reporteActual)
  if (reporte) {
    return { 
      nombre: reporte.nombre, 
      origen: 'Agencia', 
      icono: reporte.icono,
      iconoOrigen: 'fas fa-building'
    }
  }
  
  // Buscar en reportes de appweb
  reporte = reportesAppWeb.value.find(r => r.id === reportesStore.reporteActual)
  if (reporte) {
    return { 
      nombre: reporte.nombre, 
      origen: 'AppWeb', 
      icono: reporte.icono,
      iconoOrigen: 'fas fa-globe-americas'
    }
  }
  
  return { nombre: 'Reporte', origen: '', icono: 'fas fa-chart-line', iconoOrigen: '' }
})

// Título del reporte actual (para compatibilidad)
const tituloReporteActual = computed(() => infoReporteActual.value.nombre)

// Mostrar filtro de agencia según el reporte
const mostrarFiltroAgencia = computed(() => {
  const reportesConFiltroAgencia = ['ventas-tickets', 'tickets-anulados']
  return reportesConFiltroAgencia.includes(reportesStore.reporteActual)
})

// Exportación disponible solo para reportes del rol Agencia
const exportando = ref(false)
const reportesExportAgencia = ['ventas-diarias', 'tickets-devoluciones', 'sports-carreras', 'tickets-anulados']
const exportDisponible = computed(() => {
  return reportesStore.origenActual === 'agencia' && reportesExportAgencia.includes(reportesStore.reporteActual)
})
const formatoExport = ref('csv')

/**
 * Mapea el identificador del reporte actual al tipo esperado por el backend para exportación.
 * Ejemplos:
 *  - 'ventas-diarias' -> 'ventas_diarias'
 *  - 'tickets-devoluciones' -> 'tickets_devoluciones'
 */
function mapearTipoReporteExport() {
  const mapa = {
    'ventas-diarias': 'ventas_diarias',
    'tickets-devoluciones': 'tickets_devoluciones',
    'sports-carreras': 'sports_carreras',
    'tickets-anulados': 'tickets_anulados'
  }
  return mapa[reportesStore.reporteActual] || reportesStore.reporteActual
}

/**
 * Construye los filtros en el formato que espera el backend.
 */
function construirFiltrosExport() {
  const filtros = {
    fecha_desde: reportesStore.filtros.fechaDesde || undefined,
    fecha_hasta: reportesStore.filtros.fechaHasta || undefined,
    agencia_id: reportesStore.filtros.agenciaId || undefined,
    hipodromo_id: reportesStore.filtros.hipodromoId || undefined,
    numero_carrera: reportesStore.filtros.numeroCarrera || undefined
  }
  // Eliminar undefined para no enviar claves vacías
  Object.keys(filtros).forEach(k => filtros[k] === undefined && delete filtros[k])
  return filtros
}

/**
 * Ejecuta la exportación en el formato solicitado y abre la descarga.
 * Solo aplica para rol Agencia en los reportes permitidos.
 */
async function exportar(formato = 'csv') {
  if (!exportDisponible.value || exportando.value) return
  exportando.value = true
  try {
    const payload = {
      report_type: mapearTipoReporteExport(),
      format: formato,
      filters: construirFiltrosExport()
    }
    const resp = await reportesApi.exportarReporte(payload, authStore.tokenCsrf)
    const urlDescarga = resp.download_url || resp.url || resp.download || null
    if (urlDescarga) {
      // Abrir en nueva pestaña para iniciar la descarga
      window.open(urlDescarga, '_blank')
    } else {
      console.warn('Exportación generada pero sin URL de descarga', resp)
    }
  } catch (e) {
    console.error('Error exportando reporte:', e)
    alert('No se pudo exportar el reporte: ' + (e.message || 'Error desconocido'))
  } finally {
    exportando.value = false
  }
}

// KPIs filtrados (ocultar devoluciones para admin)
const kpisFiltrados = computed(() => {
  const kpis = { ...reportesStore.kpis }
  
  // Ocultar total_devoluciones para admin
  if (authStore.esAdmin && kpis.total_devoluciones !== undefined) {
    delete kpis.total_devoluciones
  }
  
  return kpis
})

/**
 * Alterna la visibilidad del menú lateral en móvil
 */
function alternarMenuLateral() {
  menuVisible.value = !menuVisible.value
}

/**
 * Alterna el menú desplegable de Informe
 */
function toggleInformeMenu() {
  menuInformeAbierto.value = !menuInformeAbierto.value
}

/**
 * Selecciona un menú y carga el contenido correspondiente
 */
async function seleccionarMenu(idMenu) {
  // Ocultar menú en móvil después de selección
  if (window.innerWidth < 768) {
    menuVisible.value = false
  }
  
  if (idMenu === 'dashboard') {
    reportesStore.limpiarEstado()
    return
  }
  
  // Establecer el reporte actual ANTES de cargar
  reportesStore.reporteActual = idMenu
  
  await cargarReporte()
}

/**
 * Selecciona un reporte específico con su origen
 */
async function seleccionarReporte(idReporte, origen) {
  // Ocultar menú en móvil después de selección
  if (window.innerWidth < 768) {
    menuVisible.value = false
  }
  
  // Establecer el reporte y origen
  reportesStore.reporteActual = idReporte
  reportesStore.origenActual = origen
  
  await cargarReporte()
}

/**
 * Carga los datos del reporte seleccionado
 */
async function cargarReporte() {
  if (!reportesStore.reporteActual) return

  try {
    reportesStore.cargando = true
    reportesStore.error = null

    const filtros = {
      fecha_desde: reportesStore.filtros.fechaDesde,
      fecha_hasta: reportesStore.filtros.fechaHasta,
      agencia_id: reportesStore.filtros.agenciaId,
      hipodromo_id: reportesStore.filtros.hipodromoId,
      numero_carrera: reportesStore.filtros.numeroCarrera,
      page: reportesStore.paginacion.paginaActual,
      limit: reportesStore.paginacion.registrosPorPagina
    }

    // Solo agregar buscar_usuario para reportes AppWeb
    if (reportesStore.origenActual === 'appweb' && reportesStore.reporteActual === 'por-usuario') {
      filtros.buscar_usuario = reportesStore.filtros.buscarUsuario
    }

    const params = new URLSearchParams()
    Object.entries(filtros).forEach(([key, value]) => {
      if (value && value !== '0' && value !== '') {
        params.append(key, value)
      }
    })
    const queryString = params.toString()
    
    let endpoint
    // Determinar endpoint según origen y rol
    if (reportesStore.origenActual === 'appweb') {
      // Reportes de AppWeb
      const mapaAppWeb = {
        'por-usuario': 'por-usuario',
        'economico': 'economico', 
        'apuestas': 'apuestas',
        'dinero-remanente': 'dinero-remanente',
        'rendimiento-apuesta-carrera': 'rendimiento-apuesta-carrera'
      }
      endpoint = `reports/appweb/${mapaAppWeb[reportesStore.reporteActual]}`
    } else if (authStore.esAgencia) {
      // Reportes de agencia
      endpoint = `reports/agencia/${reportesStore.reporteActual}`
    } else {
      // Reportes de admin para agencia
      const mapaEndpoints = {
        'informe-agencias': 'informe-por-agencia',
        'ventas-tickets': 'lista-tickets',
        'caballos-retirados': 'caballos-retirados',
        'carreras': 'carreras',
        'tickets-anulados': 'tickets-anulados',
        'informe-parte-venta': 'informe-parte-venta'
      }
      endpoint = `reports/${mapaEndpoints[reportesStore.reporteActual] || reportesStore.reporteActual}`
    }

    // Para reportes de AppWeb, usar procesamiento directo
    if (reportesStore.origenActual === 'appweb') {
      const respuesta = await apiClient.get(`/${endpoint}?${queryString}`)
      reportesStore.datosReporte = respuesta.data || []
      reportesStore.kpis = respuesta.kpis || {}
      reportesStore.paginacion.totalRegistros = respuesta.total_records || 0
    } else {
      // Para reportes de agencia, usar el store
      await reportesStore.cargarReporte(reportesStore.reporteActual)
    }
    
  } catch (err) {
    console.error('Error cargando reporte:', err)
    reportesStore.error = 'Error al cargar el reporte: ' + err.message
  } finally {
    reportesStore.cargando = false
  }
}

/**
 * Aplica los filtros seleccionados y recarga el reporte
 */
 async function aplicarFiltros() {
  await cargarReporte(reportesStore.reporteActual)
}
/**
 * Formatear título de KPI para mostrar en la UI
 */
function formatearTituloKPI(clave) {
  const titulos = {
    'total_vendido': 'Total Vendido',
    'total_ganadores': 'Total Ganadores', 
    'total_pagados': 'Total Pagados',
    'ganancia': 'Ganancia Neta',
    'total_apostado': 'Total Apostado',
    'total_anulados': 'Total Anulados',
    'total_devoluciones': 'Total Devoluciones',
    'total_general_apuestas': 'Total Apuestas',
    'total_a_devolver': 'Total a Devolver',
    'total_devuelto': 'Total Devuelto',
    'total_general_devolver': 'Total a Devolver',
    'ventas_boletos': 'Ventas Boletos',
    'cancelados': 'Cancelados',
    'retirados': 'Retirados',
    'venta_neta_boletos': 'Venta Neta Boletos'
  }
  return titulos[clave] || clave.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

/**
 * Obtener tipo de KPI para formateo
 */
function obtenerTipoKPI(clave) {
  const tiposMoneda = [
    'total_vendido', 'total_ganadores', 'total_pagados', 'ganancia', 
    'total_apostado', 'total_devoluciones', 'total_general_apuestas',
    'total_a_devolver', 'total_devuelto', 'total_general_devolver',
    'ventas_boletos', 'cancelados', 'retirados', 'venta_neta_boletos'
  ]
  return tiposMoneda.includes(clave) ? 'moneda' : 'numero'
}

/**
 * Calcular ganancia para Ventas Diarias
 */
function calcularGanancia() {
  const kpis = reportesStore.kpis
  if (!kpis) return 0
  
  const totalVendidos = kpis.total_vendidos || 0
  const totalPagados = kpis.total_pagados || 0
  const totalDevoluciones = kpis.total_devoluciones || 0
  
  return totalVendidos - totalPagados - totalDevoluciones
}

/**
 * Obtener icono para cada tipo de KPI
 */
function obtenerIconoKPI(clave) {
  const iconos = {
    'total_vendido': 'fas fa-dollar-sign',
    'total_ganadores': 'fas fa-trophy',
    'total_pagados': 'fas fa-hand-holding-usd',
    'ganancia': 'fas fa-chart-line',
    'total_apostado': 'fas fa-coins',
    'total_anulados': 'fas fa-times-circle',
    'total_devoluciones': 'fas fa-undo-alt',
    'total_general_apuestas': 'fas fa-coins',
    'total_a_devolver': 'fas fa-undo',
    'total_devuelto': 'fas fa-check-circle',
    'total_general_devolver': 'fas fa-undo'
  }
  return iconos[clave] || 'fas fa-chart-bar'
}

/**
 * Obtener color para cada tipo de KPI
 */
function obtenerColorKPI(clave) {
  const colores = {
    'total_vendido': 'orange',
    'total_ganadores': 'green',
    'total_pagados': 'blue',
    'ganancia': 'green',
    'total_apostado': 'blue',
    'total_anulados': 'red',
    'total_devoluciones': 'red',
    'ganancia_casa': 'green',
    'total_usuarios': 'orange',
    'total_premios': 'blue',
    'total_acreditado': 'blue',
    'total_ingreso_por_apuestas': 'blue',
    'total_premios_pagados': 'green',
    'diferencia': 'blue',
    'diferencia_(ganancia_casa)': 'green',
    'total_general_apuestas': 'orange',
    'total_a_devolver': 'red',
    'total_devuelto': 'green',
    'total_general_devolver': 'red'
  }
  return colores[clave] || 'gray'
}

/**
 * Formatear valor como moneda
 */
function formatearMoneda(valor) {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS'
  }).format(parseFloat(valor) || 0)
}

/**
 * Alternar expansión de detalles de usuario
 */
async function toggleDetalleUsuario(idUsuario) {
  if (usuariosExpandidos.value.has(idUsuario)) {
    // Contraer
    usuariosExpandidos.value.delete(idUsuario)
  } else {
    // Expandir y cargar detalles si no existen
    usuariosExpandidos.value.add(idUsuario)
    
    if (!detallesUsuarios.value[idUsuario]) {
      try {
        const respuesta = await apiClient.get(`/reports/appweb/detalle-usuario/${idUsuario}`)
        detallesUsuarios.value[idUsuario] = respuesta.data || []
      } catch (error) {
        console.error('Error cargando detalles del usuario:', error)
        detallesUsuarios.value[idUsuario] = []
      }
    }
  }
}

</script>
