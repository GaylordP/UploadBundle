<?php

namespace GaylordP\UploadBundle\Controller;

use GaylordP\UploadBundle\Entity\Media;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UploadController extends AbstractController
{
    private $request;
    private $uploadDirectory;
    private $translator;

    public function __construct(
        RequestStack $requestStack,
        ParameterBagInterface $parameters,
        TranslatorInterface $translator
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->uploadDirectory = $parameters->get('upload_directory');
        $this->translator = $translator;
    }

    /**
     * @Route(
     *     {
     *         "fr": "/upload",
     *     },
     *     name="upload",
     *     methods="POST"
     * )
     */
    public function upload(Request $request): Response
    {
        $uploadedFile = $request->files->get('file');

        if (null === $uploadedFile) {
            return new JsonResponse($this->translator->trans('The file could not be uploaded.', [], 'validators'), Response::HTTP_BAD_REQUEST);
        }

        if (null === $request->get('dzchunkindex')) {
            return $this->uploadSimple($uploadedFile);
        } else {
            return $this->uploadChunk($request, $uploadedFile);
        }
    }

    private function uploadSimple(UploadedFile $uploadedFile): JsonResponse
    {
        $uuid = uuid_create(UUID_TYPE_RANDOM);
        $uploadFileDirectory = '/tmp/' . $uuid . '/';

        mkdir($this->uploadDirectory . $uploadFileDirectory, 0777, true);

        $fileName = $this->getFileName($uploadedFile);
        $uploadedNewFilePath = $this->uploadDirectory . $uploadFileDirectory . $fileName;

        if ('combined' === $uploadedFile->getFilename()) {
            rename($uploadedFile->getRealPath(), $uploadedNewFilePath);
        } else {
            move_uploaded_file($uploadedFile, $uploadedNewFilePath);
        }

        $file = new File($uploadedNewFilePath);

        if (null !== $errors = $this->getErrors($file)) {
            return new JsonResponse([
                'success' => false,
                'message' => $errors,
                'path' => $uploadFileDirectory . $fileName,
            ]);
        }

        $entityManager = $this->getDoctrine()->getManager();

        $media = new Media();
        $media->setFile($file);

        $entityManager->persist($media);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'path' => $uploadFileDirectory . $fileName,
            'media_id' => $media->getId(),
        ]);
    }

    private function uploadChunk(Request $request, UploadedFile $uploadedFile): JsonResponse
    {
        $chunkUuid = $request->get('dzuuid');
        $chunkTotalParts = (int)$request->get('dztotalchunkcount') ?? 1;
        $chunkIndex = (int)$request->get('dzchunkindex');

        $chunkFileDirectory = '/tmp/chunk/' . $chunkUuid . '/';

        if (0 === $chunkIndex) {
            mkdir($this->uploadDirectory . $chunkFileDirectory, 0777, true);
        }

        $uploadedChunkNewFilePath = $this->uploadDirectory . $chunkFileDirectory . $chunkIndex;

        if (move_uploaded_file($uploadedFile, $uploadedChunkNewFilePath)) {
            if ($chunkIndex === ($chunkTotalParts - 1)) {
                return $this->combineChunk($uploadedFile, $this->uploadDirectory . $chunkFileDirectory, $chunkTotalParts);
            }

            return new JsonResponse([
                'chunk_success' => true,
            ]);
        }
    }

    private function combineChunk(
        UploadedFile $lastUploadedFile,
        string $chunkUploadDirectory,
        int $chunkTotalParts
    ): JsonResponse {
        $combinedChunkFilePath = $chunkUploadDirectory . 'combined';

        $target = fopen($combinedChunkFilePath, 'wb');

        for ($i = 0; $i < $chunkTotalParts; $i++) {
            $chunk = fopen($chunkUploadDirectory . $i, "rb");
            stream_copy_to_stream($chunk, $target);
            fclose($chunk);
        }

        fclose($target);

        for ($i = 0; $i < $chunkTotalParts; $i++) {
            unlink($chunkUploadDirectory . $i);
        }

        $newUploadedFile = new UploadedFile($combinedChunkFilePath, $lastUploadedFile->getClientOriginalName());

        $uploadSimple = $this->uploadSimple($newUploadedFile);

        rmdir($chunkUploadDirectory);
        return $uploadSimple;
    }

    private function getFileName(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);

        return $safeFilename . '-'.uniqid() . '.' . $file->guessExtension();
    }

    private function getErrors(File $file): ?string
    {
        $formType = $this->request->headers->get('form-type');
        $formData = $this->request->get('form-data');

        if (!class_exists($formType)) {
            return $this->translator->trans('The file could not be uploaded.', [], 'validators');
        } else {
            parse_str($formData, $formValues);
            $formName = array_key_first($formValues);

            $formValues[$formName]['upload'] = $file->getPathname();

            $form = $this->createForm($formType);
            $form->submit($formValues[$formName]);

            if ($form->isSubmitted() && $form->isValid()) {
                dump($form->getData());
                return ;
            } else {
                dump($form->getErrors());
                $messageList = [];

                foreach ($form->getErrors(true) as $error) {
                    $origin = $error->getOrigin()->getConfig();
                    $messageList[] = $this->translator->trans($origin->getOption('label'), [], $origin->getOption('translation_domain')) . ' : ' . $error->getMessage();
                }

                return implode(' ', $messageList);
            }

        }
    }
}
