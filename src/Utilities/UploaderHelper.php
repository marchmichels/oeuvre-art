<?php
/**
 * Author: Marc Michels
 * Date: 9/1/22
 * File: UploaderHelper.php
 * Description: The UploaderHelper Class generates unique filenames for images stored in AWS S3 returns public
 *              URLs for images stored in AWS S3.
 * Public Methods: uploadArt - takes image file from form submission and generates a unique filename
 *                             before submitting to be uploaded to AWS S3
 *                 deleteArt - takes a fileUrl as an argument and delete the associated image from AWS S3
 *                 getPublicPath - takes in fileurl property of Art entity and returns
 *                                 public url for image file in AWS S3
 */

namespace App\Utilities;

use Aws\S3\S3Client;
use Gedmo\Sluggable\Util\Urlizer;
use League\Flysystem\AdapterInterface;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{

    const ART_IMAGE = 'art_image';

    private $filesystem;    //Flysystem
    private $s3;            //AWS S3 SDK Client

    private $requestStackContext;

    private $publicAssetBaseUrl;

    // UploadHelper constructor, takes Flysystem, Symfony Stack Context,
    public function __construct(Filesystem $uploadsFilesystem, RequestStackContext $requestStackContext, string $uploadedAssetsBaseUrl, S3Client $s3Client)
    {

        $this->filesystem = $uploadsFilesystem;
        $this->requestStackContext = $requestStackContext;
        $this->publicAssetBaseUrl = $uploadedAssetsBaseUrl;
        $this->s3 = $s3Client;

    }


    // upload a file to AWS S3
    public function uploadArt(File $file): string
    {

        // get the original filename
        if ($file instanceof UploadedFile) {
            $originalFilename = $file->getClientOriginalName();
        } else {
            $originalFilename = $file->getFilename();
        }

        // generate a unique filename
        $newFilename = Urlizer::urlize(pathinfo($originalFilename, PATHINFO_FILENAME)).'-'.uniqid().'.'.$file->guessExtension();


        // write file to AWS S3
        $this->filesystem->write(
            self::ART_IMAGE.'/'.$newFilename,
            file_get_contents($file->getPathname())
        );

        // return new (unique) filename
        return $newFilename;

    }

    // delete a file from AWS S3
    public function deleteArt(String $fileUrl)
    {

        // delete a file from AWS S3
        $this->filesystem->delete(self::ART_IMAGE.'/'.$fileUrl);

    }



    public function getPublicPath(string $path): string
    {

        // s3 SDK command to get each s3 object based on url from SQL
        $cmd = $this->s3->getCommand('GetObject', [
            'Bucket' => 'oeuvreartbucket',
            'Key' => 'art_image/' . $path
        ]);


        $request = $this->s3->createPresignedRequest($cmd, '+10 minutes');

        // retrieve the public URL
        $signedUrl = (string)$request->getUri();

        return $signedUrl;


    }

    public function getMosaicPublicPath(): string
    {
        $cmd = $this->s3->getCommand('GetObject', [
            'Bucket' => 'oeuvreart-python-output',
            'Key' => 'python_mosaic.jpg'
        ]);


        $request = $this->s3->createPresignedRequest($cmd, '+10 minutes');

        // retrieve the public URL
        $signedUrl = (string)$request->getUri();

        return $signedUrl;

    }



}