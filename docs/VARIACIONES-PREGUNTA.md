# Sistema de Variaciones Tipo Pregunta

## Nuevo Enfoque ✅

En lugar de convertir fases completas en preguntas, ahora puedes **convertir variaciones individuales en preguntas**. Esto ofrece mucha más flexibilidad:

- ✅ Las phases se mantienen como siempre
- ✅ Puedes mezclar variaciones normales con variaciones-pregunta en la misma fase
- ✅ Las respuestas se guardan y pueden usarse como dependencias

## ¿Cómo Funciona?

### 1. Convertir una Variación en Pregunta

Al editar una variación, encontrarás:

**Checkbox: "Convert to Question"**
- Cuando lo activas, la variación se convierte en una pregunta
- Aparecerán campos adicionales para configurar la pregunta

### 2. Configurar la Pregunta

Una vez activado el checkbox, configura:

#### Question Key (Obligatorio)
- **Identificador único** de la pregunta
- Usa minúsculas y guiones bajos
- Ejemplo: `house_m2`, `ceiling_height`, `budget`
- **Importante:** Debe ser único en todo el configurador

#### Input Type
- **Number**: Para números (permite decimales)
- **Text**: Para texto libre

#### Placeholder
- Texto de ayuda en el campo vacío
- Ejemplo: "Introduce los m²"

#### Required
- ✅ Marcado: Campo obligatorio
- ⬜ Sin marcar: Campo opcional

### 3. El Título de la Variación
- Se usa como **etiqueta/texto de la pregunta**
- Ejemplo: Si el título es "¿Cuántos m² tiene tu casa?", eso es lo que verá el usuario

## Ejemplo Práctico

### Caso: Configurador de Puertas

#### Fase 1: "Dimensiones"

**Variación 1 (Tipo Pregunta):**
- ✅ Convert to Question: Activado
- Título: `¿Cuántos m² tiene tu vivienda?`
- Question Key: `house_m2`
- Input Type: `Number`
- Placeholder: `Ej: 75`
- Required: ✅ Activado

**Variación 2 (Tipo Pregunta):**
- ✅ Convert to Question: Activado
- Título: `¿Qué altura tienen los techos?`
- Question Key: `ceiling_height`
- Input Type: `Number`
- Placeholder: `Ej: 2.5`
- Required: ✅ Activado

#### Fase 2: "Tipo de Puerta" (Variaciones Normales)

**Variación 1:**
- Título: `Puerta Estándar`
- Precio: 450€
- Sin dependencias

**Variación 2:**
- Título: `Puerta Premium`
- Precio: 850€
- Sin dependencias

#### Fase 3: "Instalación" (Con Dependencias)

**Variación 1:**
- Título: `Instalación Básica`
- Precio: 200€
- **Depends on Question Answers:**
  - Question Key: `house_m2`
  - Operator: `<`
  - Value: `60`

**Variación 2:**
- Título: `Instalación Premium`
- Precio: 550€
- **Depends on Question Answers:**
  - Question Key: `house_m2`
  - Operator: `>`
  - Value: `100`

**Variación 3:**
- Título: `Instalación para Techos Altos`
- Precio: 450€
- **Depends on Question Answers:**
  - Question Key: `ceiling_height`
  - Operator: `>`
  - Value: `2.8`

## Flujo del Usuario

1. **Fase 1 - Dimensiones:**
   - Usuario ve 2 campos de input (las variaciones-pregunta)
   - Introduce: `house_m2 = 75` y `ceiling_height = 2.5`
   - Hace clic en "Siguiente"

2. **Fase 2 - Tipo de Puerta:**
   - Usuario ve las variaciones normales
   - Selecciona: "Puerta Premium"
   - Hace clic en "Siguiente"

3. **Fase 3 - Instalación:**
   - Sistema evalúa respuestas: `house_m2 = 75`, `ceiling_height = 2.5`
   - Filtra variaciones:
     - ❌ Instalación Básica (75 no es < 60)
     - ✅ Instalación Premium aparece (pero no se muestra porque 75 no es > 100)
     - ❌ Techos Altos (2.5 no es > 2.8)
   - Usuario ve solo las opciones que aplican

## Ventajas del Nuevo Enfoque

### ✅ Mayor Flexibilidad
- Puedes mezclar preguntas con variaciones normales en la misma fase
- No necesitas crear fases separadas

### ✅ Más Intuitivo
- Cada variación puede ser pregunta o selección
- El título de la variación es la pregunta

### ✅ Mejor Organización
- Las phases mantienen su estructura original
- Fácil de configurar y mantener

### ✅ Combinaciones Potentes
**Ejemplo de fase mixta:**
- Variación 1 (Normal): Tipo A - 100€
- Variación 2 (Normal): Tipo B - 150€
- Variación 3 (Pregunta): ¿Cuántas unidades necesitas? (quantity)
- Variación 4 (Pregunta): ¿Cuándo lo necesitas? (text)

## Estructura de Datos en Sesión

### Respuestas de Preguntas (Global)
```php
$_SESSION['pbc_questions'] = [
    'house_m2' => '75',
    'ceiling_height' => '2.5',
    'budget' => '5000'
];
```

### Session del Configurador (Mantiene compatibilidad)
```php
$_SESSION['pbc_variation_123'][1] = [
    'phase' => [
        'id' => 456,
        'name' => 'Dimensiones'
    ],
    'var' => [
        'id' => 789,
        'name' => '¿Cuántos m²?: 75',
        'type' => 'question',
        'price' => 0
    ],
    'question_key' => 'house_m2',
    'question_answer' => '75'
];
```

## Configuración de Dependencias

### En Variaciones

**Campo:** "Depends on Question Answers"

**Opciones:**
- **Question Key**: El key de la pregunta (ej: `house_m2`)
- **Operator**: `>`, `>=`, `<`, `<=`, `=`, `!=`
- **Value**: Valor para comparar

**Ejemplo:**
```
Para mostrar solo si la casa tiene más de 60m²:
- Question Key: house_m2
- Operator: >
- Value: 60
```

### Múltiples Condiciones

Puedes añadir varias condiciones. La variación se muestra solo si **TODAS** se cumplen (AND).

**Ejemplo:**
```
Condición 1:
- Question Key: house_m2
- Operator: >
- Value: 60

Condición 2:
- Question Key: ceiling_height
- Operator: >=
- Value: 2.5

Resultado: Se muestra solo si m² > 60 Y altura >= 2.5
```

## Operadores de Comparación

### Para Números
Todos los operadores funcionan:
- `>` Mayor que
- `>=` Mayor o igual que
- `<` Menor que
- `<=` Menor o igual que
- `=` Igual a
- `!=` Diferente de

### Para Texto
Solo estos operadores:
- `=` Igual a
- `!=` Diferente de

## Estilos CSS

Las variaciones-pregunta tienen clases especiales:

```css
.variation_list.is-question { /* Contenedor de la pregunta */ }
.variation-question-label { /* Etiqueta de la pregunta */ }
.pbc_question_input { /* Campo de input */ }
.pbc_question_input:focus { /* Estado focus */ }
.pbc_question_input:required { /* Campo requerido */ }
```

Puedes personalizarlos en `pbc-configurator.css`

## Diferencias con el Enfoque Anterior

| Característica | Enfoque Anterior | Nuevo Enfoque ✅ |
|----------------|------------------|------------------|
| Dónde se configuran | En la Phase | En cada Variación |
| Flexibilidad | Toda la fase o nada | Por variación |
| Mezclar tipos | No | Sí |
| Configuración | Más compleja | Más simple |
| Mantenimiento | Phases especiales | Todo igual |

## Casos de Uso

### ✅ Recopilar Datos Técnicos
- Dimensiones (m², altura, largo, ancho)
- Cantidades (unidades, metros lineales)
- Especificaciones técnicas

### ✅ Información del Cliente
- Presupuesto disponible
- Fecha deseada
- Preferencias especiales

### ✅ Cálculos Personalizados
- Precio por m²
- Descuentos por volumen
- Recargos por condiciones

### ✅ Filtrado Inteligente
- Mostrar opciones según medidas
- Recomendar según presupuesto
- Adaptar según características

## Compatibilidad

- ✅ Compatible con variaciones normales
- ✅ Compatible con dependencias de variaciones
- ✅ Compatible con ambos templates (wizard/vertical)
- ✅ Compatible con el sistema de sesiones existente
- ✅ Compatible con precios y opciones

## Testing

### Caso de Prueba 1: Variación Pregunta
1. Crea una variación
2. Activa "Convert to Question"
3. Configura: key=`test_m2`, type=Number
4. Guarda y prueba en frontend
5. Verifica que aparece un input

### Caso de Prueba 2: Dependencia
1. Crea variación-pregunta con key=`test_value`
2. Crea variación normal con dependencia:
   - Question Key: `test_value`
   - Operator: `>`
   - Value: `50`
3. Prueba con valor 60 → debe aparecer
4. Prueba con valor 40 → no debe aparecer

### Caso de Prueba 3: Fase Mixta
1. En una fase, crea:
   - 2 variaciones normales (radio buttons)
   - 1 variación-pregunta (input)
2. Verifica que todas se muestran correctamente
3. Verifica que se guarda todo en sesión

## Troubleshooting

### Los inputs no aparecen
- ✅ Verifica que el checkbox "Convert to Question" está activado
- ✅ Asegúrate de que el Question Key no está vacío
- ✅ Recarga el caché si es necesario

### Las dependencias no funcionan
- ✅ Verifica que el Question Key coincide exactamente
- ✅ Comprueba el operador y valor
- ✅ Revisa que la pregunta se responde antes

### Las respuestas no se guardan
- ✅ Verifica que los campos tienen name correcto
- ✅ Comprueba que el formulario tiene el nonce
- ✅ Revisa la configuración de sesiones en PHP

## Próximas Mejoras

1. **Mostrar respuestas en resumen**
   - Incluir las respuestas en el summary final
   - Mostrarlas en el PDF generado

2. **Validación avanzada**
   - Mínimo y máximo para números
   - Patrones para texto

3. **Cálculos con respuestas**
   - Precio = base * m2
   - Descuentos según cantidad

4. **Select Options**
   - Añadir soporte para select/dropdown
   - Opciones predefinidas

## Validación y Flujo

### Validación Automática
- ✅ **HTML5 Validation**: Los campos requeridos se validan automáticamente
- ✅ **JavaScript Validation**: Alerta si faltan preguntas requeridas
- ✅ **No auto-selección**: Las fases con solo preguntas no se saltan automáticamente
- ✅ **Focus automático**: El primer input recibe el foco al cargar la fase

### Comportamiento del Botón "Siguiente"
- Si hay preguntas requeridas sin responder → Muestra alerta y no avanza
- Si hay variaciones normales sin seleccionar → HTML5 validation lo maneja
- Si todo está completo → Avanza normalmente

### Mezcla de Tipos
Si una fase tiene:
- **Solo preguntas**: No se auto-selecciona nada, usuario debe responder
- **Solo variaciones**: Se auto-selecciona la primera (comportamiento normal)
- **Ambos**: Se auto-selecciona la primera variación normal, las preguntas deben responderse

## Resumen

**Este nuevo enfoque es mucho más flexible y potente:**

✅ Las phases se mantienen normales  
✅ Cada variación puede ser pregunta o selección  
✅ Puedes mezclar ambos tipos en la misma fase  
✅ Las dependencias funcionan igual  
✅ Más fácil de configurar y mantener  
✅ Validación automática de preguntas requeridas  
✅ No se saltan fases con preguntas sin responder  

**¡El sistema está listo para usar!** 🚀

## Validación Robusta (Doble Capa)

### Validación Frontend (JavaScript)
El sistema valida en el cliente para mejor UX:

```javascript
// Solo valida al hacer click en "Siguiente"
$(document).on('submit', '#configurator-form', function(e) {
    var submitType = $(document.activeElement).attr('value');
    if (submitType !== 'next') return true;
    
    var questionInputs = $('.pbc_question_input');
    var allAnswered = true;
    var missingRequired = [];
    
    questionInputs.each(function() {
        var $input = $(this);
        var isRequired = $input.prop('required') || $input.attr('required') === 'required';
        var value = $.trim($input.val());
        
        if (isRequired && value === '') {
            allAnswered = false;
            missingRequired.push(/* nombre pregunta */);
            $input.addClass('error-field'); // Borde rojo
        } else {
            $input.removeClass('error-field');
        }
    });
    
    if (!allAnswered) {
        e.preventDefault();
        e.stopPropagation();
        alert('Por favor, responde todas las preguntas requeridas:\n- ' + missingRequired.join('\n- '));
        $('.pbc_question_input.error-field').first().focus();
        return false;
    }
});
```

**Características:**
- ✅ Validación solo al avanzar (no al retroceder)
- ✅ Alerta con lista de preguntas faltantes
- ✅ Focus automático en primer campo con error
- ✅ Indicadores visuales (borde rojo + fondo rosa)
- ✅ Asterisco rojo (*) en labels de campos requeridos

### Validación Backend (PHP)
Validación de seguridad en servidor:

```php
$should_advance = true;
$validation_error = '';

if ( 'next' === $submit ) {
    $current_phase_id = $phases[ $current_step_from_form - 1 ];
    $phase_variations = get_posts( /* obtener variaciones */ );
    $required_question_keys = array();
    
    // Identificar preguntas requeridas
    foreach ( $phase_variations as $var_id ) {
        $is_question = get_post_meta( $var_id, 'pbc_is_question', true );
        $is_required = get_post_meta( $var_id, 'pbc_question_required', true );
        $question_key = get_post_meta( $var_id, 'pbc_question_key', true );
        
        if ( $is_question && $is_required && $question_key ) {
            $required_question_keys[] = $question_key;
        }
    }
    
    // Validar respuestas
    if ( ! empty( $required_question_keys ) ) {
        foreach ( $required_question_keys as $req_key ) {
            $answer = isset( $_POST['pbc_question'][ $req_key ] ) ? 
                     trim( sanitize_text_field( wp_unslash( $_POST['pbc_question'][ $req_key ] ) ) ) : '';
            
            if ( '' === $answer ) {
                $should_advance = false;
                $validation_error = 'Por favor, responde todas las preguntas requeridas';
                break;
            }
        }
    }
}

if ( ! $should_advance ) {
    $cstep = $current_step_from_form; // No avanzar
    $_SESSION['pbc_output'] = array(
        'type' => 'error',
        'response' => '<div class="error">' . esc_html( $validation_error ) . '</div>',
    );
}
```

**Características:**
- ✅ Sanitización completa de inputs
- ✅ Validación con `trim()` (previene espacios)
- ✅ Mensaje de error persistente en sesión
- ✅ Fallback si JavaScript está deshabilitado
- ✅ Compatible con WordPress Coding Standards

### Indicadores Visuales

#### CSS para Estados de Error
```css
.pbc_question_input.error-field {
  border-color: #d32f2f !important;
  border-width: 2px !important;
  background-color: #ffebee;
}

.pbc_question_input:required {
  border-left: 3px solid #9a781f;
}

.required-asterisk {
  color: #d32f2f;
  font-weight: bold;
}
```

**Experiencia Visual:**
- 🔴 Asterisco rojo (*) en campos requeridos
- 🔴 Borde rojo en campos con error
- 🟥 Fondo rosa claro en campos con error
- 🟡 Borde izquierdo dorado en campos requeridos
- ✨ Focus automático en primer error

### Prevención de Auto-Skip de Fases con Preguntas

El sistema incluye protección contra el auto-salto de fases que contienen preguntas:

```javascript
// En pbc-configurator.js - después de cargar respuesta AJAX
var hasVariations = $('.page-configurator').find('input.pbc_variation').length > 0;
var hasQuestions = $('.page-configurator').find('.pbc_question_input').length > 0;

// Solo auto-saltar si NO hay variaciones NI preguntas
if (newNextPhase && !hasVariations && !hasQuestions) {
    // Auto-skip fase vacía
    $('.page-configurator').find('button[name=submit]').trigger('click');
} else {
    // Fase tiene contenido - esperar interacción del usuario
}
```

**¿Por qué es importante?**
- Sin esta protección, fases con solo preguntas se saltarían automáticamente
- El sistema detecta inputs `.pbc_question_input` y DETIENE el auto-skip
- El usuario debe rellenar las preguntas y hacer click en "Siguiente"

### Flujo de Validación Completo

1. **Usuario llega a una fase:**
   - Si tiene preguntas → Se DETIENE (no auto-skip)
   - Si está vacía → Auto-skip al siguiente paso
2. **Usuario rellena campos y hace click en "Siguiente"**
3. **JavaScript valida:**
   - Revisa todos los `.pbc_question_input[required]`
   - Si hay vacíos → Muestra alerta + marca errores + focus
   - Si todo OK → Permite submit
4. **PHP valida (backend):**
   - Recibe `$_POST['pbc_question']`
   - Verifica campos requeridos
   - Sanitiza valores con `trim()` y `sanitize_text_field()`
   - Si hay vacíos → No avanza + muestra error
   - Si todo OK → Guarda en `$_SESSION['pbc_questions']` y avanza

### Casos Especiales

#### Solo preguntas no requeridas
- ✅ Se puede avanzar sin responder
- ✅ No se muestran asteriscos
- ✅ No hay validación

#### Mix: requeridas + opcionales
- ✅ Solo valida las requeridas
- ✅ Acepta opcionales vacías
- ✅ Mensaje específico solo para requeridas

#### Solo variaciones normales
- ✅ HTML5 validation maneja la selección
- ✅ No interfiere con preguntas

