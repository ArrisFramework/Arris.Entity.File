<?php

namespace Arris\Entity;

use RuntimeException;
use InvalidArgumentException;

class File implements FileInterface
{
    const FM_READ = 'r';
    const FM_RW = 'r+';
    const FM_WRITE = 'w+';
    const FM_APPEND = 'a+';
    const FM_CREATE = 'c+';

    /**
     * @var string Путь к файлу
     */
    private string $path;

    /**
     * @var array Кешированные данные pathinfo
     */
    private array $pathinfo;

    /**
     * @var int Размер файла в байтах
     */
    private int $size;

    /**
     * @var string MIME-тип файла
     */
    private string $mimeType;

    /**
     * @var int Время последнего изменения
     */
    private int $lastModifiedTime;

    public bool $is_exists = false;

    public bool $is_temp = false;

    public bool $is_opened = false;

    /**
     * @var false|resource
     */
    public $handler;


    /**
     * File constructor.
     *
     * @param string $path Путь к файлу
     *
     * @throws InvalidArgumentException Если файл не существует
     * @throws RuntimeException Если не удалось прочитать метаданные
     */
    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException("File does not exist: {$path}");
        }

        $this->path = $path;
        $this->pathinfo = pathinfo($path);

        $this->initFileMetadata();
    }

    /**
     * Инициализирует метаданные файла
     * @throws RuntimeException
     */
    private function initFileMetadata(): void
    {
        $size = filesize($this->path);
        if ($size === false) {
            throw new RuntimeException("Failed to get file size: {$this->path}");
        }

        $this->size = $size;

        $mimetype = mime_content_type($this->path);
        if ($mimetype === false) {
            throw new RuntimeException("Failed to detect MIME type: {$this->path}");
        }

        $this->mimeType = $mimetype;

        $file_mod_time = filemtime($this->path);
        if ($file_mod_time === false) {
            throw new RuntimeException("Failed to get last modified time: {$this->path}");
        }
        $this->lastModifiedTime = $file_mod_time;

        $this->is_exists = true;
    }

    /**
     * Получить содержимое файла
     * @param int $position
     * @param int|null $length
     * @return string
     */
    public function getContent(int $position = 0, ?int $length = null): string
    {
        // @todo: если position не ноль - сдвигаем указатель, если length null - читаем до упора. Как вернуть статус последней операции?
        // @todo: getLastStatus или getStatus (очевидно что LAST)

        //@TODO

        $content = file_get_contents($this->path);
        if ($content === false) {
            throw new RuntimeException("Failed to read file: {$this->path}");
        }
        return $content;
    }

    /**
     * Записать содержимое в файл
     *
     * @param string $content
     * @param int $flag, с флагом FILE_APPEND - допишет
     * @todo: ТЕСТЫ
     * @return int Количество записанных байт
     */
    public function putContent(string $content, int $flag = 0): int
    {
        $bytes = file_put_contents($this->path, $content, $flag);
        if ($bytes === false) {
            throw new RuntimeException("Failed to write to file: {$this->path}");
        }

        // Обновляем метаданные после изменения файла
        $this->initFileMetadata();
        return $bytes;
    }

    /**
     * Открывает файл, создавая файловый хэндлер
     *
     * @param string $mode
     * @return $this
     */
    public function open(string $mode = self::FM_APPEND):self
    {
        $this->handler = fopen($this->path, $mode);

        $this->is_opened = true;

        return $this;
    }

    /**
     * Закрывает файловый хэндлер
     *
     * @param bool $just_in_case - Действия "на всякий случай". Если FALSE - требует, чтобы файл был ОТКРЫТ.
     * @return $this
     */
    public function close(bool $just_in_case = true):self
    {
        if (!$this->is_opened && !$just_in_case) {
            throw new RuntimeException("Can't close, file not opened: " . $this->path);
        }

        if ($this->is_opened) {
            fclose($this->handler);
        }

        $this->is_opened = false;

        if ($this->is_temp) {
            $this->delete(true);
        }

        return $this;

        /*if ($this->is_opened) {
            fclose($this->handler);
            $this->is_opened = false;

            if ($this->is_temp) {
                $this->delete(true);
            }

            return $this;
        }

        if (!$this->is_opened && !$just_in_case) {
            throw new RuntimeException("Can't close, file not opened: " . $this->path);
        }

        return $this;*/
    }

    /**
     * Удалить файл
     * @param bool $just_in_case
     * @return bool
     */
    public function delete(bool $just_in_case = true): bool
    {
        $unlink = unlink($this->path);
        if ($unlink === false && !$just_in_case) {
            if (!file_exists($this->path)) {
                throw new RuntimeException("File not exists: {$this->path}");
            }

            throw new RuntimeException("Unable to delete file: {$this->path}");
        }
        $this->is_exists = false;
        return true;
    }

    /**
     * Урезает файл до указанной длины
     *
     * @param int $size
     * @return bool
     */
    public function truncate(int $size = 0):bool
    {
        $handler = $this->is_opened ? $this->handler : fopen($this->path, 'a+');
        $is_truncate = ftruncate($handler, $size);

        // Обновляем метаданные после изменения файла
        $this->initFileMetadata();
        return $is_truncate;
    }

    /**
     * Переместить файл
     *
     * @todo: доработать, если целевой путь не содержит имени - нужно использовать текущее
     *
     * @param string $newPath
     * @return bool
     */
    public function move_old(string $newPath): bool
    {
        $result = rename($this->path, $newPath);
        if ($result) {
            $this->path = $newPath;
            $this->pathinfo = pathinfo($newPath);
            $this->initFileMetadata();
        }
        return $result;
    }

    public function move(string $newPath): bool
    {
        // Если путь заканчивается на разделитель директорий
        if (str_ends_with($newPath, DIRECTORY_SEPARATOR)) {
            // Получаем текущее имя файла
            $currentName = $this->pathinfo['basename'] ?? '';
            if ($currentName === '') {
                throw new RuntimeException('Cannot determine current filename');
            }
            // Добавляем имя файла к целевому пути
            $newPath .= $currentName;
        }

        $result = rename($this->path, $newPath);
        if ($result) {
            $this->path = $newPath;
            $this->pathinfo = pathinfo($newPath);
            $this->initFileMetadata();
        }
        return $result;
    }

    /**
     * Копировать файл
     * @param string $targetPath
     * @return File Новый экземпляр файла
     * @throws RuntimeException Если не удалось скопировать
     */
    public function copy(string $targetPath): self
    {
        if (!copy($this->path, $targetPath)) {
            throw new RuntimeException("Failed to copy file from {$this->path} to {$targetPath}");
        }
        return new self($targetPath);
    }

    /**
     * Получить расширение файла (без точки!)
     * @return string
     */
    public function getExtension(): string
    {
        return $this->pathinfo['extension'] ?? '';
    }

    /**
     * Получить имя файла (без пути)
     * @return string
     */
    public function getFilename(): string
    {
        return $this->pathinfo['basename'];
    }


    /**
     * Получить имя файла без расширения
     * @return string
     */
    public function getFilenameWithoutExtension(): string
    {
        return $this->pathinfo['filename'];
    }

    /**
     * Получить путь к директории файла
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->pathinfo['dirname'];
    }

    /**
     * Получить размер файла в байтах
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Alias of getSize()
     * @return int
     */
    public function getLength():int
    {
        return $this->size;
    }

    /**
     * Получить MIME-тип файла
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Получить время последнего изменения файла (timestamp)
     * @return int
     */
    public function getLastModifiedTime(): int
    {
        return $this->lastModifiedTime;
    }

    /**
     * Проверить, является ли файл читаемым
     * @return bool
     */
    public function isReadable(): bool
    {
        return is_readable($this->path);
    }

    /**
     * Проверить, является ли файл записываемым
     * @return bool
     */
    public function isWritable(): bool
    {
        return is_writable($this->path);
    }

    /**
     * Проверить, является ли файл исполняемым
     * @return bool
     */
    public function isExecutable(): bool
    {
        return is_executable($this->path);
    }

    /**
     * Проверяет, является ли файл символической ссылкой
     * @return bool
     */
    public function isLink():bool
    {
        return is_link($this->path);
    }

    /**
     * Получить хэш файла
     * @param string $algorithm Алгоритм хэширования (по умолчанию sha256)
     * @return string
     * @throws RuntimeException Если не удалось вычислить хэш
     */
    public function getHash(string $algorithm = 'sha256'): string
    {
        $hash = hash_file($algorithm, $this->path);
        if ($hash === false) {
            throw new RuntimeException("Failed to calculate file hash: {$this->path}");
        }
        return $hash;
    }

    /**
     * Получить путь к файлу
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Проверить, является ли файл изображением
     * @return bool
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }

    /**
     * Проверить, является ли файл видео
     * @return bool
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mimeType, 'video/');
    }

    /**
     * Создать новый файл
     *
     * @param string $path
     * @param string $content
     * @return self
     * @throws RuntimeException Если не удалось создать файл
     */
    public static function create(string $path, string $content = ''): self
    {
        if (file_exists($path)) {
            throw new InvalidArgumentException("File already exists: {$path}");
        }

        $bytes = file_put_contents($path, $content, LOCK_EX);
        if ($bytes === false) {
            throw new RuntimeException("Failed to create file: {$path}");
        }

        return new self($path);
    }

    /**
     * Создает новый временный файл с префиксом в системном каталоге для временных файлов
     *
     * @param string $prefix
     * @param string $content
     * @return static
     */
    public static function createTemp(string $prefix = '',  string $content = ''):self
    {
        $temp_file = tempnam(sys_get_temp_dir(), $prefix);

        if ($temp_file === false) {
            throw new RuntimeException("Can't create temporary file at " . sys_get_temp_dir());
        }

        if (!empty($content)) {
            $bytes = file_put_contents($temp_file, $content, LOCK_EX);
            if ($bytes === false) {
                throw new RuntimeException("Failed to write data to temporary file: {$temp_file}");
            }
        }

        $file = new self($temp_file);
        $file->is_temp = true;
        return $file;
    }

    /**
     * Проверить существование файла
     * @return bool
     */
    public function exists(): bool
    {
        return file_exists($this->path);
    }

    /* === */

    /**
     * Возвращает UID и GID владельца файла
     * @return array
     */
    public function getFileOwner(): array
    {
        $uid = fileowner($this->path);
        $gid = filegroup($this->path);
        $u_info = posix_getpwuid($uid);
        $g_info = posix_getgrgid($gid);
        return [
            'uid'   =>  $u_info['uid'],
            'gid'   =>  $g_info['gid'],
            'name'  =>  $u_info['name'],
            'group' =>  $g_info['name'],
            'dir'   =>  $u_info['dir'],
            'shell' =>  $u_info['shell']
        ];
    }

    /**
     * Записывает данные в файл с указанной позиции
     *
     * @param string $content Данные для записи
     * @param int $position Позиция для начала записи
     * @return int Количество записанных байт
     * @throws RuntimeException Если не удалось выполнить запись
     */
    public function writeFromPosition(string $content, int $position):int
    {
        if (!$this->is_opened) {
            $this->open(self::FM_RW);
        }

        if (fseek($this->handler, $position) === -1) {
            throw new RuntimeException("Failed to seek to position {$position} in file: {$this->path}");
        }

        $bytes = fwrite($this->handler, $content);
        if ($bytes === false) {
            throw new RuntimeException("Failed to write to file: {$this->path}");
        }

        // Обновляем метаданные после изменения файла
        $this->initFileMetadata();
        return $bytes;
    }

    /**
     * Читает данные из файла с указанной позиции
     *
     * @param int $position Позиция для начала чтения
     * @param int|null $length Количество байт для чтения (null - до конца файла)
     * @return string Прочитанные данные
     * @throws RuntimeException Если не удалось выполнить чтение
     */
    public function readFromPosition(int $position = 0, ?int $length = null):string
    {
        if (!$this->is_opened) {
            $this->open(self::FM_READ);
        }

        if (fseek($this->handler, $position) === -1) {
            throw new RuntimeException("Failed to seek to position {$position} in file: {$this->path}");
        }

        $content = $length === null
            ? fread($this->handler, $this->size - $position)
            : fread($this->handler, $length);

        if ($content === false) {
            throw new RuntimeException("Failed to read from file: {$this->path}");
        }

        return $content;
    }


}