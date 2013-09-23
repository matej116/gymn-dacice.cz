<?php

/**
 * model for loading filename of photos
 */
class PhotoStorage extends UploadFileStorage {

	/* For administration *************************************************/

	public function save(HttpUploadedFile $file, $prefix = '', $maxSize = 0) {
		$image = Image::fromFile($file->getTemporaryFile());
		if ($maxSize > 0) {
			$image->resize($maxSize, $maxSize, Image::FIT | Image::SHRINK_ONLY);
		}
		$filename = $prefix . $file->getName();
		$filename  = $this->getNonExistentFilename($filename);
		$image->save($this->prefix($filename));
		return $filename;
	}

}
