<?php

namespace Directus;

use Directus\Application\Application;
use Directus\Filesystem\Thumbnail;
use Directus\Util\ArrayUtils;

if (!function_exists('is_uploaded_file_okay')) {
    /**
     * Checks whether upload file has not error.
     *
     * @param $error
     *
     * @return bool
     */
    function is_uploaded_file_okay($error)
    {
        return $error === UPLOAD_ERR_OK;
    }
}

if (!function_exists('get_uploaded_file_error')) {
    /**
     * Returns the upload file error message
     *
     * Returns null if there's not error
     *
     * @param $error
     *
     * @return string|null
     */
    function get_uploaded_file_error($error)
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                $message = 'The uploaded file exceeds max upload size that was specified on the server.';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'The uploaded file exceeds the max upload size that was specified in the client.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = 'The uploaded file was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = 'No file was uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = 'Missing temporary upload folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = 'Failed to write file to disk.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = 'A PHP extension stopped the file upload';
                break;
            case UPLOAD_ERR_OK:
                $message = null;
                break;
            default:
                $message = 'Unknown error uploading a file.';
        }

        return $message;
    }
}

if (!function_exists('get_uploaded_file_status')) {
    /**
     * Returns the upload file http status code
     *
     * @param $error
     *
     * @return int|null
     */
    function get_uploaded_file_status($error)
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                $code = 413;
                break;
            case UPLOAD_ERR_FORM_SIZE:
            case UPLOAD_ERR_PARTIAL:
            case UPLOAD_ERR_NO_FILE:
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
            default:
                $code = null;
                break;
            case UPLOAD_ERR_OK:
                $code = 200;
                break;
        }

        return $code;
    }
}

if (!function_exists('append_storage_information')) {
    /**
     * append storage information to one or multiple file items
     *
     * @param array $rows
     *
     * @return array
     */
    function append_storage_information(array $rows, array $params = [])
    {

        if (empty($rows)) {
            return $rows;
        }

        $fields =  get_files_fields($params['fields']);

        $container = Application::getInstance()->getContainer();

        $config = $container->get('config');
        $fileRootUrl = $config->get('storage.root_url');
        $hasFileRootUrlHost = parse_url($fileRootUrl, PHP_URL_HOST);
        $isLocalStorageAdapter = $config->get('storage.adapter') == 'local';
        $list = isset($rows[0]);

        if (!$list) {
            $rows = [$rows];
        }

        foreach ($rows as &$row) {
            $data = [];
            $data['url'] = $data['full_url'] = $fileRootUrl . '/' . $row['filename'];

            // Add Full url
            if ($isLocalStorageAdapter && !$hasFileRootUrlHost) {
                $data['full_url'] = get_url($data['url']);
            }

            // Add Thumbnails
            $data['thumbnails'] = get_thumbnails($row);

            // Add embed content
            /** @var \Directus\Embed\EmbedManager $embedManager */
            $embedManager = $container->get('embed_manager');
            $provider = isset($row['type']) ? $embedManager->getByType($row['type']) : null;
            $embed = null;
            if ($provider) {
                $embed = [
                    'html' => $provider->getCode($row),
                    'url' => $provider->getUrl($row)
                ];
            }

            $data['embed'] = $embed;

            if (!empty($fields) && !in_array('*', $fields)) {
                $data = ArrayUtils::pick($data, $fields);
            }

            $row['data'] = $data;
        }

        return $list ? $rows : reset($rows);
    }
}

if (!function_exists('add_default_dimensions')) {
    /**
     * Adds the default dimensions to the dimension list
     *
     * @param array $list
     */
    function add_default_thumbnail_dimensions(array &$list)
    {
        $defaultDimension = '200x200';
        if (!in_array($defaultDimension, $list)) {
            array_unshift($list, $defaultDimension);
        }
    }
}

if (!function_exists('get_thumbnails')) {
    /**
     * Returns the row thumbnails data
     *
     * @param array $row
     *
     * @return array|null
     */
    function get_thumbnails(array $row)
    {
        $filename = $row['filename'];
        $type = array_get($row, 'type');
        $thumbnailFilenameParts = explode('.', $filename);
        $thumbnailExtension = array_pop($thumbnailFilenameParts);
        $thumbnailDimensions = array_filter(
            explode(',', get_directus_setting('thumbnail_dimensions'))
        );

        if (!$type || (strpos($type, 'image/') !== 0 && strpos($type, 'embed/') !== 0)) {
            return null;
        }

        // Add default size
        add_default_thumbnail_dimensions($thumbnailDimensions);

        $thumbnails = [];
        foreach (array_unique($thumbnailDimensions) as $dimension) {
            if (Thumbnail::isNonImageFormatSupported($thumbnailExtension)) {
                $thumbnailExtension = Thumbnail::defaultFormat();
            }

            if (!is_string($dimension)) {
                continue;
            }

            $size = explode('x', $dimension);
            if (count($size) == 2) {
                $thumbnailUrl =  get_thumbnail_url($filename, $size[0], $size[1]);
                $thumbnailRelativeUrl = get_thumbnail_path($filename, $size[0], $size[1]);
                $thumbnails[] = [
                    'url' => $thumbnailUrl,
                    'relative_url' => $thumbnailRelativeUrl,
                    'dimension' => $dimension,
                    'width' => (int)$size[0],
                    'height' => (int)$size[1]
                ];
            }
        }

        return $thumbnails;
    }
}

if (!function_exists('get_thumbnail_url')) {
    /**
     * Returns a url for the given file pointing to the thumbnailer
     *
     * @param string $name
     * @param int $width
     * @param int $height
     * @param string $mode
     * @param string $quality
     *
     * @return string
     */
    function get_thumbnail_url($name, $width, $height, $mode = 'crop', $quality = 'good')
    {
        return get_url(get_thumbnail_path($name, $width, $height, $mode, $quality));
    }
}

if (!function_exists('get_thumbnail_path')) {
    /**
     * Returns a relative url for the given file pointing to the thumbnailer
     *
     * @param string $name
     * @param int $width
     * @param int $height
     * @param string $mode
     * @param string $quality
     *
     * @return string
     */
    function get_thumbnail_path($name, $width, $height, $mode = 'crop', $quality = 'good')
    {
        $projectName = get_api_project_from_request();

        // env/width/height/mode/quality/name
        return sprintf(
            '/thumbnail/%s/%d/%d/%s/%s/%s',
            $projectName,
            $width,
            $height,
            $mode,
            $quality,
            $name
        );
    }
}

if (!function_exists('filename_put_ext')) {
    /**
     * Appends an extension to a filename
     *
     * @param string $name
     * @param string|null $ext
     *
     * @return string
     */
    function filename_put_ext($name, $ext = null)
    {
        if ($ext) {
            $name = sprintf('%s.%s', $name, $ext);
        }

        return $name;
    }
}

if (!function_exists('is_a_url')) {
    /**
     * Checks whether or not the given value is a URL
     *
     * @param string $value
     *
     * @return bool
     */
    function is_a_url($value)
    {
        if (!is_string($value)) {
            return false;
        }

        if (preg_match('#^data:.+\/.+;base64,#si', $value)) {
            return false;
        }

        // Ported from: https://github.com/segmentio/is-url/blob/master/index.js
        if (!preg_match('#^(?:\w+:)?\/\/(.+)$#si', $value, $matches)) {
            return false;
        }

        $hostAndPath = $matches[1];
        if (!$hostAndPath) {
            return false;
        }

        if (preg_match('#^[^\s\.]+\.\S{2,}$#si', $hostAndPath)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('get_files_fields')) {
    /**
     * This function return only fields related to files.
     *
     * @param array $params
     *
     * @return array|null
     */
    function get_files_fields($params)
    {
        $fileParams = [];
        foreach ($params as $param) {
            $paramAry = \explode('.', $param);
            if ($paramAry[0] === 'data') {
                $fileParams[] = $paramAry[1];
            }
        }
        return $fileParams;
    }
}
