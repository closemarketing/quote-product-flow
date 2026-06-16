# Testing Guide for Quote Product Flow

Este documento describe cómo configurar y ejecutar tests unitarios para el plugin Quote Product Flow.

## Requisitos Previos

- PHP 7.4 o superior
- Composer instalado
- MySQL o MariaDB
- SVN (Subversion) instalado

## Instalación del Entorno de Tests

### 1. Instalar Dependencias

Primero, instala las dependencias de desarrollo de Composer:

```bash
composer install
```

### 2. Configurar el Entorno de Tests de WordPress

Ejecuta el script de instalación del entorno de tests:

```bash
composer test-install
```

O manualmente con parámetros personalizados:

```bash
bash bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]
```

Por ejemplo:

```bash
bash bin/install-wp-tests.sh wordpress_test root 'root' 127.0.0.1 latest
```

**Parámetros:**
- `db-name`: Nombre de la base de datos de tests (se creará si no existe)
- `db-user`: Usuario de MySQL
- `db-pass`: Contraseña de MySQL
- `db-host`: Host de MySQL (por defecto: localhost)
- `wp-version`: Versión de WordPress a usar (por defecto: latest)

⚠️ **Importante**: La base de datos especificada será recreada cada vez que ejecutes el script, así que usa una base de datos dedicada para tests.

## Ejecutar Tests

### Ejecutar Todos los Tests

```bash
composer test
```

O directamente con phpunit:

```bash
vendor/bin/phpunit
```

### Ejecutar Tests Específicos

Ejecutar una clase de test específica:

```bash
vendor/bin/phpunit tests/Unit/CalcTest.php
```

Ejecutar un test específico:

```bash
vendor/bin/phpunit --filter test_calculate_color_text_with_dark_background
```

### Ejecutar Tests con Depuración

Para ejecutar tests con Xdebug:

```bash
composer test-debug
```

### Ver Cobertura de Código

Para generar un reporte de cobertura de código (requiere Xdebug):

```bash
vendor/bin/phpunit --coverage-html coverage
```

Luego abre `coverage/index.html` en tu navegador.

## Estructura de Tests

```
tests/
├── bootstrap.php          # Carga WordPress y el plugin
├── Unit/                  # Tests unitarios
│   ├── CalcTest.php      # Tests para clase CALC
│   └── AdminPluginTest.php # Tests para QPFW_Admin_Plugin
└── phpstan-bootstrap.php  # Bootstrap para PHPStan (análisis estático)
```

## Escribir Nuevos Tests

### Crear una Nueva Clase de Test

1. Crea un archivo en `tests/Unit/` con el nombre `{ClaseName}Test.php`
2. Extiende de `WP_UnitTestCase`
3. Usa el namespace `Close\PBC\Tests\Unit`

Ejemplo:

```php
<?php
namespace Close\QPFW\Tests\Unit;

use WP_UnitTestCase;
use Close\QPFW\Helpers\CALC;

class CalcTest extends WP_UnitTestCase {
    
    public function test_example() {
        $result = CALC::calculate_color_text( '#000000' );
        $this->assertEquals( '#ffffff', $result );
    }
}
```

### Métodos de Aserciones Disponibles

- `assertEquals()` - Verifica igualdad
- `assertNotEquals()` - Verifica desigualdad
- `assertTrue()` / `assertFalse()` - Verifica booleanos
- `assertContains()` - Verifica que un array contiene un valor
- `assertArrayHasKey()` - Verifica que un array tiene una clave
- `assertInstanceOf()` - Verifica el tipo de objeto
- `assertMatchesRegularExpression()` - Verifica con regex

## Helpers de WordPress en Tests

### Factory

El objeto `factory` permite crear posts, usuarios, etc:

```php
// Crear post
$post_id = $this->factory->post->create();

// Crear usuario
$user_id = $this->factory->user->create( array(
    'role' => 'administrator',
) );
```

### Setup y Teardown

```php
public function setUp(): void {
    parent::setUp();
    // Código antes de cada test
}

public function tearDown(): void {
    // Código después de cada test
    parent::tearDown();
}
```

## Integración Continua

Los tests se ejecutan automáticamente en GitHub Actions cuando:

- Se hace push a las ramas `trunk`, `main`, `master` o `develop`
- Se abre o actualiza un Pull Request

La configuración está en `.github/workflows/phpunit.yml`

## Solución de Problemas

### Error: "Could not find wp-tests-config.php"

Ejecuta `composer test-install` para configurar el entorno de tests.

### Error de Conexión a MySQL

Verifica que MySQL está corriendo y que las credenciales son correctas:

```bash
mysql -u root -p -e "SHOW DATABASES;"
```

### Tests Muy Lentos

1. Asegúrate de que Xdebug está deshabilitado si no estás depurando
2. Considera usar una base de datos en memoria

## Mejores Prácticas

1. **Cada test debe ser independiente**: No dependas del orden de ejecución
2. **Limpia después de cada test**: Usa `tearDown()` para limpiar datos
3. **Nombres descriptivos**: `test_should_return_white_text_for_dark_background()`
4. **Un concepto por test**: Cada test debe verificar una sola cosa
5. **Usa data providers**: Para testear múltiples casos similares

## Recursos

- [WordPress PHPUnit Reference](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Core Test Suite](https://make.wordpress.org/core/handbook/testing/)

## Contacto

Para preguntas sobre los tests, contacta con el equipo de desarrollo en Close·marketing.

