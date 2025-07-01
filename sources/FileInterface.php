<?php

namespace Arris\Entity;

class OperationState
{
    public bool $success;
    public ?string $operation;
    public int $bytes_processed;
    public ?int $position;
    public ?string $error;
}
interface FileInterface
{
    public function __construct(string $path);
    // public function getState(): OperationState;

    public static function create(string $path, string $content = ''): self;
    public static function createTemp(string $prefix = ''):self;

    public function open(string $mode = File::FM_APPEND):self;

    public function exists(): bool;

    public function getContent(int $position = 0, ?int $length = null): string;
    public function putContent(string $content, int $flag = 0): int;

    public function copy(string $targetPath): self;
    public function move(string $newPath): bool;

    public function delete(): bool;
    public function truncate(int $size = 0):bool;

    public function getPath(): string;
    public function getExtension(): string;
    public function getFilename(): string;
    public function getFilenameWithoutExtension(): string;
    public function getDirectory(): string;
    public function getSize(): int;
    public function getLength():int;
    public function getMimeType(): string;
    public function getLastModifiedTime(): int;

    public function isReadable(): bool;
    public function isWritable(): bool;
    public function isExecutable(): bool;
    public function isLink():bool;

    public function getHash(string $algorithm = 'sha256'): string;

    public function getFileOwner(): array;

    public function isImage(): bool;
    public function isVideo(): bool;

    public function writeFromPosition(string $content, int $position):int;
    public function readFromPosition(int $position = 0, ?int $length = null):string;

    public static function match(string $pattern, string $test, int $flags):bool;

}