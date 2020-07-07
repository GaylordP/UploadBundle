<?php

namespace GaylordP\UploadBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UploadController extends AbstractController
{
    private $requestStack;
    private $uploadDirectory;
    private $router;
    private $translator;

    public function __construct(
        RequestStack $requestStack,
        ParameterBagInterface $parameters,
        RouterInterface $router,
        TranslatorInterface $translator
    ) {
        $this->requestStack = $requestStack;
        $this->uploadDirectory = $parameters->get('upload_directory');
        $this->router = $router;
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

        $formData = $this->requestStack->getCurrentRequest()->get('form-data');
        parse_str($formData, $formValues);
        $formName = array_key_first($formValues);
        $formValues[$formName][$this->requestStack->getCurrentRequest()->headers->get('form-upload-name')] = $uploadedNewFilePath;
        $this->requestStack->getCurrentRequest()->request->set($formName, $formValues[$formName]);

        $routerMatches = $this->router->match($this->requestStack->getCurrentRequest()->headers->get('form-url'));
        $routerMatchesController = $routerMatches['_controller'];
        $routerMatchesParams = $routerMatches;
        unset($routerMatchesParams['_route'], $routerMatchesParams['_controller']);

        return $this->forward($routerMatchesController, $routerMatchesParams);
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

            return new JsonResponse('chunk_success', Response::HTTP_OK);
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
}
