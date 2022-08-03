<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
//use League\Flysystem\AdapterInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    final const ARTICLE_IMAGE = 'article_image';
    final const ARTICLE_REFERENCE = 'article_reference';

    final const VISIBILITY_PUBLIC = 'public';
    final const VISIBILITY_PRIVATE = 'private';

    public function __construct(private readonly FilesystemOperator $uploadsFilesystem,

                                private readonly RequestStackContext $requestStackContext,
                                private readonly LoggerInterface $logger,
                                private readonly string $uploadedAssetsBaseUrl)
    {
    }

    public function uploadArticleImage(File $file, ?string $existingFilename): string
    {
        $this->logger->info(sprintf("Uploading %s to %s", $file->getRealPath(), $existingFilename));
        $newFilename = $this->uploadFile($file, self::ARTICLE_IMAGE, true);

        if ($existingFilename) {
            try {
                $this->uploadsFilesystem->delete(self::ARTICLE_IMAGE.'/'.$existingFilename);
            } catch (FilesystemException) {
                $this->logger->alert(sprintf('Old uploaded file "%s" was missing when trying to delete', $existingFilename));
            }
        }

        return $newFilename;
    }

    public function uploadArticleReference(File $file): string
    {
        return $this->uploadFile($file, self::ARTICLE_REFERENCE, false);
    }

    public function getPublicPath(string $path): string
    {
        $fullPath = $this->uploadedAssetsBaseUrl.'/'.$path;
        // if it's already absolute, just return
        if (str_contains($fullPath, '://')) {
            return $fullPath;
        }

        // needed if you deploy under a subdirectory
        return $this->requestStackContext
            ->getBasePath().$fullPath;
    }

    /**
     * @return resource
     */
    public function readStream(string $path)
    {
        $resource = $this->filesystem->readStream($path);

        if ($resource === false) {
            throw new \Exception(sprintf('Error opening stream for "%s"', $path));
        }

        return $resource;
    }

    public function deleteFile(string $path)
    {
        $result = $this->filesystem->delete($path);

        if ($result === false) {
            throw new \Exception(sprintf('Error deleting "%s"', $path));
        }
    }

    private function uploadFile(File $file, string $directory, bool $isPublic): string
    {
//        dd($file);
        $this->logger->info(sprintf("Uploading %s to directory %s (%s)", $file->getRealPath(), $directory, $isPublic ? 'public' : 'private'));

        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }
        $newFilename = Urlizer::urlize(pathinfo($originalFilename, PATHINFO_FILENAME)).'.'.$file->guessExtension();

        $stream = fopen($file->getPathname(), 'r');
        $location = $directory.'/'.$newFilename;
        try {
            $this->uploadsFilesystem->writeStream(
               $location,
                $stream,
                [
                    'visibility' => $isPublic ? self::VISIBILITY_PUBLIC : self::VISIBILITY_PRIVATE
                ]
            );
        } catch (\Exception $exception) {
            dd($exception);
            throw new \Exception(sprintf('Could not write uploaded file "%s"', $newFilename));
        }

        if (is_resource($stream)) {
            fclose($stream);
        }
        dd($newFilename, $location, $this->uploadsFilesystem, $this->uploadsFilesystem->has($location));

        return $newFilename;
    }
}
