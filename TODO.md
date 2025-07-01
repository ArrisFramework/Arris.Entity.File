# Todo

## open()

возможно, имеет смысл переписать так, чтобы конструктор никогда не кидал исключение, но для работы с файлом сначала нужно было вызывать
```php 
->open()
```
Чтобы именно `open()` создавал все структуры, а конструктор только запоминает имя... и может быть, вызывает open если есть параметр
`force_open = false`. Это даст защиту от исключения в конструкторе.

## `OperationState`

```php
<?php

namespace Arris\Entity\File;

class OperationState
{
    public bool $success;
    public ?string $operation;
    public int $bytes_processed;
    public ?int $position;
    public ?string $error;
}

/**
 * Сбрасывает состояние операций
 */
private function resetState(): void
{
    $this->lastOperationState = new OperationState();
    $this->lastOperationState->success = false;
    $this->lastOperationState->operation = null;
    $this->lastOperationState->bytes_processed = 0;
    $this->lastOperationState->position = null;
    $this->lastOperationState->error = null;
}
```
