# Configuración de Tests Unitarios - Resumen

## ✅ Archivos Creados

Se han creado los siguientes archivos para configurar los tests unitarios:

### Configuración Principal
- ✅ `phpunit.xml.dist` - Configuración de PHPUnit
- ✅ `bin/install-wp-tests.sh` - Script para instalar entorno de tests de WordPress
- ✅ `tests/bootstrap.php` - Carga WordPress y el plugin en los tests

### Tests Unitarios
- ✅ `tests/Unit/CalcTest.php` - Tests para la clase CALC (helpers de cálculo)
  - Tests para `calculate_color_text()` - Calcula color de texto según fondo
  - Tests para `adjust_brightness()` - Ajusta brillo de colores
  - Tests para `get_show_prices_for_user()` - Configuración de precios por rol
  - Tests para `get_total_from_enquiry()` - Cálculo de totales

- ✅ `tests/Unit/AdminPluginTest.php` - Tests para QPFW_Admin_Plugin
  - Tests de instanciación de la clase
  - Tests de hooks registrados
  - Tests de métodos públicos

### Configuración CI/CD
- ✅ `.github/workflows/phpunit.yml` - GitHub Actions para ejecutar tests automáticamente

### Archivos Actualizados
- ✅ `composer.json` - Añadidas dependencias y scripts de test
- ✅ `.gitignore` - Ignorar archivos de cache de PHPUnit
- ✅ `.distignore` - Excluir tests de la distribución

### Documentación
- ✅ `docs/TESTING.md` - Guía completa de testing

## 🚀 Próximos Pasos

### 1. Instalar Dependencias

```bash
cd /Users/davidperez/Web/puertas-alpu/app/public/wp-content/plugins/quote-product-flow
composer install
```

### 2. Configurar Entorno de Tests

```bash
composer test-install
```

Esto instalará WordPress en una carpeta temporal y configurará una base de datos de tests.

### 3. Ejecutar Tests

```bash
composer test
```

## 📦 Dependencias Añadidas

En `composer.json` se han añadido:

```json
"require-dev": {
    "yoast/phpunit-polyfills": "^1.0",
    "wp-phpunit/wp-phpunit": "^6.3"
}
```

## 🔧 Scripts de Composer Disponibles

```bash
composer test           # Ejecutar todos los tests
composer test-debug     # Ejecutar tests con Xdebug
composer test-install   # Instalar entorno de tests de WordPress
```

## 📊 Cobertura de Tests Actual

### Clase CALC
- ✅ `calculate_color_text()` - 3 tests
- ✅ `adjust_brightness()` - 2 tests  
- ✅ `get_show_prices_for_user()` - 4 tests
- ✅ `get_total_from_enquiry()` - 2 tests

Total: **11 tests unitarios**

### Clase QPFW_Admin_Plugin
- ✅ Instanciación - 1 test
- ✅ Hooks registrados - 1 test
- ✅ Métodos públicos - 3 tests

Total: **5 tests unitarios**

## 🎯 Tests Recomendados a Añadir

Para mejorar la cobertura, considera añadir tests para:

1. **Helpers/PDF**: Tests para generación de PDFs
2. **Helpers/ShowParts**: Tests para mostrar partes del configurador
3. **Helpers/ShowTemplate**: Tests para templates
4. **PBC_Request**: Tests para manejo de peticiones AJAX
5. **QPFW_Public**: Tests para funcionalidad pública

## 📝 Notas Importantes

- Los tests se ejecutan en una base de datos separada (`wordpress_test`)
- La base de datos de tests se recrea cada vez que ejecutas `test-install`
- GitHub Actions ejecutará los tests automáticamente en cada push y PR
- Los tests cubren PHP 7.4, 8.1, 8.2 y 8.3

## 🐛 Troubleshooting

Si encuentras problemas:

1. Verifica que MySQL está corriendo
2. Asegúrate de tener SVN instalado: `svn --version`
3. Verifica permisos de escritura en `/tmp`
4. Consulta `docs/TESTING.md` para más detalles

## ✨ Siguientes Mejoras

- [ ] Añadir tests de integración
- [ ] Configurar coverage reports
- [ ] Añadir tests para AJAX endpoints
- [ ] Añadir tests para generación de PDFs
- [ ] Configurar mutation testing con Infection

---

**Documentación completa**: Ver `docs/TESTING.md`

