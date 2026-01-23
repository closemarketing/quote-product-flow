# Función check_phases_options()

## Descripción

La función `CALC::check_phases_options()` permite validar múltiples phases (fases) según diferentes condiciones y criterios. Es útil para verificar si una o varias phases cumplen con requisitos específicos antes de procesarlas.

## Sintaxis

```php
CALC::check_phases_options( $phase_ids, $options );
```

## Parámetros

### $phase_ids
- **Tipo:** `array|int`
- **Requerido:** Sí
- **Descripción:** ID o array de IDs de phases a validar

### $options
- **Tipo:** `array`
- **Requerido:** No
- **Descripción:** Array de opciones de validación con las siguientes claves:

| Opción | Tipo | Por defecto | Descripción |
|--------|------|-------------|-------------|
| `published` | `bool` | `true` | Verifica si las phases están publicadas |
| `parent` | `int|null` | `null` | Verifica si las phases tienen un parent específico |
| `meta_conditions` | `array` | `[]` | Array de condiciones meta_key => valor |
| `all_must_pass` | `bool` | `true` | Si true, todas deben pasar. Si false, al menos una |

## Valor de Retorno

Retorna un array con la siguiente estructura:

```php
array(
    'valid'      => bool,    // true si la validación pasa según all_must_pass
    'passed_ids' => array(), // IDs de phases que pasaron la validación
    'failed_ids' => array(), // IDs de phases que fallaron la validación
    'details'    => array(   // Detalles por cada phase ID
        $phase_id => array(
            'passed'  => bool,
            'reasons' => array() // Razones de fallo si procede
        )
    )
)
```

## Ejemplos de Uso

### Ejemplo 1: Verificar si phases están publicadas

```php
// Verificar si las phases 12, 15 y 18 están publicadas
$phase_ids = array( 12, 15, 18 );
$result = CALC::check_phases_options( $phase_ids );

if ( $result['valid'] ) {
    echo 'Todas las phases están publicadas';
} else {
    echo 'Phases fallidas: ' . implode( ', ', $result['failed_ids'] );
}
```

### Ejemplo 2: Verificar phases con un parent específico

```php
// Verificar si las phases pertenecen al parent 5
$phase_ids = array( 12, 15 );
$options = array(
    'parent' => 5,
);
$result = CALC::check_phases_options( $phase_ids, $options );

if ( $result['valid'] ) {
    echo 'Todas las phases pertenecen al parent correcto';
}
```

### Ejemplo 3: Verificar condiciones de metadatos

```php
// Verificar si las phases tienen un meta específico
$phase_ids = array( 12, 15, 18 );
$options = array(
    'meta_conditions' => array(
        'pbc_phase_type' => 'standard',
        'pbc_active'     => '1',
    ),
);
$result = CALC::check_phases_options( $phase_ids, $options );

if ( $result['valid'] ) {
    echo 'Todas las phases cumplen las condiciones de meta';
}
```

### Ejemplo 4: Permitir valores múltiples en meta

```php
// Verificar si el meta tiene uno de varios valores posibles
$phase_ids = array( 12, 15 );
$options = array(
    'meta_conditions' => array(
        'pbc_phase_type' => array( 'standard', 'premium', 'custom' ),
    ),
);
$result = CALC::check_phases_options( $phase_ids, $options );
```

### Ejemplo 5: Al menos una phase debe pasar (OR logic)

```php
// Verificar si al menos UNA phase cumple las condiciones
$phase_ids = array( 12, 15, 18 );
$options = array(
    'all_must_pass'   => false, // Cambiado a false para lógica OR
    'meta_conditions' => array(
        'pbc_featured' => '1',
    ),
);
$result = CALC::check_phases_options( $phase_ids, $options );

if ( $result['valid'] ) {
    echo 'Al menos una phase es destacada';
    echo 'Phases destacadas: ' . implode( ', ', $result['passed_ids'] );
}
```

### Ejemplo 6: Validación completa con múltiples condiciones

```php
// Validación compleja con múltiples condiciones
$phase_ids = array( 12, 15, 18, 20 );
$options = array(
    'published'       => true,
    'parent'          => 5,
    'meta_conditions' => array(
        'pbc_phase_type' => array( 'standard', 'premium' ),
        'pbc_active'     => '1',
        'pbc_stock'      => 'available',
    ),
    'all_must_pass'   => true,
);
$result = CALC::check_phases_options( $phase_ids, $options );

// Mostrar detalles de cada phase
foreach ( $result['details'] as $phase_id => $detail ) {
    echo "Phase ID {$phase_id}: ";
    if ( $detail['passed'] ) {
        echo "✓ Pasó la validación\n";
    } else {
        echo "✗ Falló por: " . implode( ', ', $detail['reasons'] ) . "\n";
    }
}
```

### Ejemplo 7: Integración en template

```php
// Usar en el template para filtrar phases válidas
$parent_phase = 5;
$all_phases = get_posts( array(
    'post_type'   => 'phases',
    'post_parent' => $parent_phase,
    'fields'      => 'ids',
) );

// Validar que las phases tengan variaciones disponibles
$options = array(
    'meta_conditions' => array(
        'pbc_has_variations' => '1',
    ),
);
$result = CALC::check_phases_options( $all_phases, $options );

// Usar solo las phases válidas
$valid_phases = $result['passed_ids'];
foreach ( $valid_phases as $phase_id ) {
    // Renderizar solo phases con variaciones
    echo get_the_title( $phase_id );
}
```

## Razones de Fallo Comunes

La función puede retornar las siguientes razones de fallo en el array `reasons`:

- `not_published`: La phase no está publicada o no existe
- `parent_mismatch`: El parent de la phase no coincide con el esperado
- `meta_{meta_key}_not_in_expected`: El valor del meta no está en los valores esperados
- `meta_{meta_key}_mismatch`: El valor del meta no coincide con el esperado

## Notas

1. La función acepta tanto un ID individual como un array de IDs
2. Las condiciones de meta soportan tanto valores únicos como arrays de valores posibles
3. Con `all_must_pass = true` (default), todas las phases deben pasar
4. Con `all_must_pass = false`, al menos una phase debe pasar
5. La función retorna información detallada para debugging

## Casos de Uso

- Validar phases antes de mostrarlas en el configurador
- Verificar dependencias entre phases
- Filtrar phases según metadatos específicos
- Comprobar que las phases tienen el parent correcto
- Validar disponibilidad de phases antes de procesar pedidos
- Debugging y troubleshooting de configuraciones de phases

