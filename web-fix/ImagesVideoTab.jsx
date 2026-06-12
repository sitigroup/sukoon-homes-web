import { useCallback, useMemo, useEffect, useRef, useState } from 'react'
import { useDropzone } from 'react-dropzone'
import { FaTimes } from 'react-icons/fa'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { useTranslation } from '@/components/context/TranslationContext'

// Images & Video Tab Component
const ImagesVideoTab = ({
    showLoader,
    handleCheckRequiredFields,
    mediaFormData,
    setMediaFormData,
    handleNewFileUpload,
    isEditing = false,
    isProperty = false,
    handleRemoveGalleryImages,
    handleRemoveDocuments,
    handleRemoveMedia: parentHandleRemoveMedia,
    handleTabChange,
    isCustomVideoUpload,
    setShouldRemoveVideo,
    existingTitleImageUrl = null,
    onRemoveExistingTitleImage
}) => {
    const t = useTranslation();

    // Create refs to store object URLs to prevent recreation on every render
    const titleImageUrls = useRef(new Map());
    const gallery3DUrls = useRef(new Map());
    const galleryImageUrls = useRef(new Map());

    // Clean up object URLs when component unmounts
    useEffect(() => {
        // Capture current ref values for cleanup
        const titleImgUrls = titleImageUrls.current;
        const gallery3DImgUrls = gallery3DUrls.current;
        const galleryImgUrls = galleryImageUrls.current;

        return () => {
            // Revoke all object URLs to prevent memory leaks
            [...titleImgUrls.values(),
            ...gallery3DImgUrls.values(),
            ...galleryImgUrls.values()].forEach(url => {
                URL.revokeObjectURL(url);
            });
        };
    }, []);

    // Create or get cached object URL for a file
    const getObjectUrl = useCallback((file, urlMap) => {
        if (!file) return '';
        if (typeof file === 'string') return file;
        if (!(file instanceof Blob)) {
            return file.url || file.image || file.name || '';
        }

        const fileId = `${file.name || 'upload'}-${file.size || 0}-${file.lastModified || 0}`;

        if (!urlMap.current.has(fileId)) {
            const url = URL.createObjectURL(file);
            urlMap.current.set(fileId, url);
        }

        return urlMap.current.get(fileId);
    }, []);

    // Generic media removal handler
    const handleRemoveMedia = useCallback((mediaType, index) => {
        // When in editing mode and parent provided a removal handler, use it
        if (isEditing && parentHandleRemoveMedia) {
            parentHandleRemoveMedia(mediaType, index);
            return;
        }

        // Default behavior for non-editing mode
        setMediaFormData(prev => {
            const updatedMedia = [...prev[mediaType]];
            updatedMedia.splice(index, 1);
            return {
                ...prev,
                [mediaType]: updatedMedia
            };
        });
    }, [isEditing, parentHandleRemoveMedia, setMediaFormData]);

    // Title Image Handler
    const onDropTitleImage = useCallback((acceptedFiles) => {
        if (isEditing && handleNewFileUpload) {
            handleNewFileUpload('titleImages', acceptedFiles);
        } else {
            setMediaFormData(prev => ({
                ...prev,
                titleImages: acceptedFiles
            }));
        }
    }, [isEditing, handleNewFileUpload, setMediaFormData]);

    const {
        getRootProps: getTitleImageRootProps,
        getInputProps: getTitleImageInputProps,
        isDragActive: isTitleImageDragActive
    } = useDropzone({
        onDrop: onDropTitleImage,
        accept: {
            'image/jpeg': ['.jpeg', '.jpg'],
            'image/png': ['.png'],
            'image/webp': ['.webp']
        },
        multiple: false
    });

    // Document Handler
    const onDropDocuments = useCallback((acceptedFiles) => {
        if (isEditing && handleNewFileUpload) {
            handleNewFileUpload('documents', acceptedFiles);
        } else {
            setMediaFormData(prev => ({
                ...prev,
                documents: [...prev.documents, ...acceptedFiles]
            }));
        }
    }, [isEditing, handleNewFileUpload, setMediaFormData]);

    const removeDocument = useCallback((index, docId) => {
        if (isEditing && handleRemoveDocuments) {
            handleRemoveDocuments(index, docId);
            return;
        }

        handleRemoveMedia('documents', index);
    }, [isEditing, handleRemoveDocuments, handleRemoveMedia]);

    const {
        getRootProps: getDocumentRootProps,
        getInputProps: getDocumentInputProps,
        isDragActive: isDocumentDragActive
    } = useDropzone({
        onDrop: onDropDocuments,
        accept: {
            'application/pdf': ['.pdf'],
            'application/msword': ['.doc', '.docx'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': ['.docx']
        },
        multiple: true
    });

    // 3D Image Handler
    const onDrop3DImage = useCallback((acceptedFiles) => {
        if (isEditing && handleNewFileUpload) {
            handleNewFileUpload('images3D', acceptedFiles);
        } else {
            setMediaFormData(prev => ({
                ...prev,
                images3D: acceptedFiles
            }));
        }
    }, [isEditing, handleNewFileUpload, setMediaFormData]);

    const {
        getRootProps: get3DImageRootProps,
        getInputProps: get3DImageInputProps,
        isDragActive: is3DImageDragActive
    } = useDropzone({
        onDrop: onDrop3DImage,
        accept: {
            'image/jpeg': ['.jpeg', '.jpg'],
            'image/png': ['.png'],
            'image/webp': ['.webp']
        },
        multiple: false
    });

    // Gallery Images Handler
    const onDropGallery = useCallback((acceptedFiles) => {
        if (isEditing && handleNewFileUpload) {
            handleNewFileUpload('galleryImages', acceptedFiles);
        } else {
            setMediaFormData(prev => ({
                ...prev,
                galleryImages: [...prev.galleryImages, ...acceptedFiles]
            }));
        }
    }, [isEditing, handleNewFileUpload, setMediaFormData]);

    const removeGalleryImage = useCallback((index, id) => {
        if (isEditing && handleRemoveGalleryImages) {
            handleRemoveGalleryImages(index, id);
            return;
        }

        handleRemoveMedia('galleryImages', index);
    }, [isEditing, handleRemoveGalleryImages, handleRemoveMedia]);

    const {
        getRootProps: getGalleryRootProps,
        getInputProps: getGalleryInputProps,
        isDragActive: isGalleryDragActive
    } = useDropzone({
        onDrop: onDropGallery,
        accept: {
            'image/jpeg': ['.jpeg', '.jpg'],
            'image/png': ['.png'],
            'image/webp': ['.webp']
        },
        multiple: true
    });

    // Video type state
    const [videoType, setVideoType] = useState(mediaFormData.videoType);

    // Video link handler
    const handleVideoInputChange = (e) => {
        const value = e.target.value;
        setMediaFormData(prev => ({ ...prev, videoLink: value }));
        if (isEditing && setShouldRemoveVideo) {
            // Only set to true if both fields will be empty
            setShouldRemoveVideo(value === "" && !mediaFormData.customVideo);
        }
    };

    // Custom video upload handler
    const onDropCustomVideo = useCallback((acceptedFiles) => {
        if (!acceptedFiles.length) return;
        if (isEditing && handleNewFileUpload) {
            handleNewFileUpload('customVideo', acceptedFiles);
        } else {
            setMediaFormData(prev => ({ ...prev, customVideo: acceptedFiles[0] }));
        }
        if (isEditing && setShouldRemoveVideo) {
            setShouldRemoveVideo(false);
        }
    }, [isEditing, handleNewFileUpload, setMediaFormData, setShouldRemoveVideo]);

    const {
        getRootProps: getVideoRootProps,
        getInputProps: getVideoInputProps,
        isDragActive: isVideoDragActive
    } = useDropzone({
        onDrop: onDropCustomVideo,
        accept: {
            'video/mp4': ['.mp4'],
            'video/webm': ['.webm'],
            'video/ogg': ['.ogg'],
            'video/quicktime': ['.mov'],
        },
        multiple: false
    });

    // Render title images
    const titleImageFiles = useMemo(() =>
        mediaFormData.titleImages.map((file, index) => (
            <div key={index} className="relative mb-2 rounded-md overflow-hidden w-full">
                <img
                    src={getObjectUrl(file, titleImageUrls)}
                    alt={`preview-${index}`}
                    className="w-full h-[150px] object-cover"
                />
                <button
                    className="absolute top-2 right-2 bg-black rounded-full p-1 text-white hover:bg-gray-800"
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        handleRemoveMedia('titleImages', index);
                    }}
                >
                    <FaTimes size={14} />
                </button>
                <div className="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs px-2 py-1 flex justify-between items-center">
                    <div className="flex items-center">
                        <span className="text-xs font-medium mr-1">{file.name || t("file")}</span>
                    </div>
                    <span className="text-xs">{Math.round(file.size / 1024)} KB</span>
                </div>
            </div>
        )),
        [mediaFormData.titleImages, handleRemoveMedia, t, getObjectUrl]);

    // Render documents
    const documentFiles = useMemo(() =>
        mediaFormData.documents.map((file, index) => (
            <div key={index} className="relative mb-2 p-3 bg-white border rounded-md flex items-center justify-between w-full">
                <div className="flex items-center gap-2 overflow-hidden">
                    <span className="truncate">{file.name}</span>
                </div>
                <div className="flex items-center gap-2">
                    <span className="text-xs text-gray-500">{Math.round(file.size / 1024)} KB</span>
                    <button
                        className="bg-black rounded-full p-1 text-white hover:bg-gray-800"
                        onClick={(e) => {
                            e.stopPropagation();
                            removeDocument(index, file.id);
                        }}
                    >
                        <FaTimes size={14} />
                    </button>
                </div>
            </div>
        )),
        [mediaFormData.documents, removeDocument, t]);

    // Render 3D images
    const files3D = useMemo(() =>
        mediaFormData.images3D.map((file, index) => (
            <div key={index} className="relative mb-2 rounded-md overflow-hidden w-full">
                <img
                    src={getObjectUrl(file, gallery3DUrls)}
                    alt={`preview-${index}`}
                    className="w-full h-[150px] object-cover"
                />
                <button
                    className="absolute top-2 right-2 bg-black rounded-full p-1 text-white hover:bg-gray-800"
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        handleRemoveMedia('images3D', index);
                    }}
                >
                    <FaTimes size={14} />
                </button>
                <div className="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs px-2 py-1 flex justify-between items-center">
                    <div className="flex items-center">
                        <span className="text-xs font-medium mr-1">{file.name || t("file")}</span>
                    </div>
                    <span className="text-xs">{Math.round(file.size / 1024)} KB</span>
                </div>
            </div>
        )),
        [mediaFormData.images3D, handleRemoveMedia, t, getObjectUrl]);

    // Render gallery images
    const galleryFiles = useMemo(() =>
        mediaFormData.galleryImages.map((file, index) => (
            <div key={index} className="relative mb-2 rounded-md overflow-hidden w-full">
                <img
                    src={getObjectUrl(file, galleryImageUrls)}
                    alt={`preview-${index}`}
                    className="w-full h-[150px] object-cover"
                />
                <button
                    className="absolute top-2 right-2 bg-black rounded-full p-1 text-white hover:bg-gray-800"
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        removeGalleryImage(index, file.id);
                    }}
                >
                    <FaTimes size={14} />
                </button>
                <div className="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs px-2 py-1 flex justify-between items-center">
                    <div className="flex items-center">
                        <span className="text-xs font-medium mr-1">{file.name || 'File'}</span>
                    </div>
                    <span className="text-xs">{Math.round(file.size / 1024)} KB</span>
                </div>
            </div>
        )),
        [mediaFormData.galleryImages, removeGalleryImage, getObjectUrl, t]);

    // Handler for the submit button
    const handleSubmit = () => {
        // This will call parent's handleCheckRequiredFields which will do validation
        // and if no error, it will call handlePostProperty or handleEditProperty
        handleCheckRequiredFields("imagesVideo", null);
    };

    const handleVideoTypeChange = (value) => {
        setVideoType(value);
        setMediaFormData(prev => ({
            ...prev,
            videoType: value,
            // Clear the opposing field when switching types
            ...(value === 'customVideo'
                ? { videoLink: '' }
                : { customVideo: null }
            )
        }));
        if (isEditing && setShouldRemoveVideo && value === 'customVideo') {
            setShouldRemoveVideo(true);
        }
    }

    return (
        <div className="flex flex-col gap-8">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {/* Title Images */}
                <div className="space-y-2">
                    <Label className="font-medium text-gray-800">
                        {t("titleImg")} <span className="text-red-500">*</span>
                    </Label>
                    {mediaFormData.titleImages.length > 0 ? (
                        <div className="rounded-md overflow-hidden">{titleImageFiles}</div>
                    ) : existingTitleImageUrl ? (
                        <div className="relative mb-2 rounded-md overflow-hidden w-full">
                            <img
                                src={existingTitleImageUrl}
                                alt={t("titleImg")}
                                className="w-full h-[150px] object-cover"
                            />
                            {onRemoveExistingTitleImage && (
                                <button
                                    type="button"
                                    className="absolute top-2 right-2 bg-black rounded-full p-1 text-white hover:bg-gray-800"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        onRemoveExistingTitleImage();
                                    }}
                                >
                                    <FaTimes size={14} />
                                </button>
                            )}
                        </div>
                    ) : (
                        <div className="primaryBackgroundBg rounded-md">
                            <div
                                {...getTitleImageRootProps()}
                                className={`border border-dashed p-4 rounded-md cursor-pointer transition-colors duration-200 flex flex-col items-center justify-center min-h-[120px] ${isTitleImageDragActive ? 'border-primary bg-primary/5' : 'border-gray-300'}`}
                            >
                                <input {...getTitleImageInputProps()} />
                                <div className="text-center text-gray-500">
                                    {isTitleImageDragActive ? (
                                        <span>{t("dropFiles")}</span>
                                    ) : (
                                        <span>
                                            {t("dragDrop")}{" "}
                                            <span className="underline">{t("browse")}</span>
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Gallery Images */}
                <div className="space-y-2">
                    <Label className="font-medium text-gray-800">
                        {t("galleryImages")}
                    </Label>
                    <div className="primaryBackgroundBg rounded-md">
                        <div
                            {...getGalleryRootProps()}
                            className={`border border-dashed p-4 rounded-md cursor-pointer transition-colors duration-200 flex flex-col items-center justify-center min-h-[120px] ${isGalleryDragActive ? 'border-primary bg-primary/5' : 'border-gray-300'}`}
                        >
                            <input {...getGalleryInputProps()} />
                            <div className="w-full">
                                <div className="text-center text-gray-500 mb-3">
                                    {isGalleryDragActive ? (
                                        <span>{t("dropgallaryFiles")}</span>
                                    ) : (
                                        <span>
                                            {t("dragDrop")}{" "}
                                            <span className="underline">{t("browse")}</span>
                                        </span>
                                    )}
                                </div>
                                {mediaFormData.galleryImages.length > 0 && (
                                    <div className="w-full space-y-2 mt-4 grid grid-cols-1 gap-2">
                                        {galleryFiles}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* 3D Images */}
                <div className="space-y-2">
                    <Label className="font-medium text-gray-800">
                        {t("3dImage")}
                    </Label>
                    {mediaFormData.images3D.length > 0 ? (
                        <div className="rounded-md overflow-hidden">{files3D}</div>
                    ) : (
                        <div className="primaryBackgroundBg rounded-md">
                            <div
                                {...get3DImageRootProps()}
                                className={`border border-dashed p-4 rounded-md cursor-pointer transition-colors duration-200 flex flex-col items-center justify-center min-h-[120px] ${is3DImageDragActive ? 'border-primary bg-primary/5' : 'border-gray-300'}`}
                            >
                                <input {...get3DImageInputProps()} />
                                <div className="text-center text-gray-500">
                                    {is3DImageDragActive ? (
                                        <span>{t("drop3dFiles")}</span>
                                    ) : (
                                        <span>
                                            {t("drag3dFiles")}{" "}
                                            <span className="underline">{t("browse")}</span>
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Documents */}
                <div className="space-y-2">
                    <Label className="font-medium text-gray-800">
                        {t("docs")}
                    </Label>
                    <div className="primaryBackgroundBg rounded-md">
                        <div
                            {...getDocumentRootProps()}
                            className={`border border-dashed p-4 rounded-md cursor-pointer transition-colors duration-200 flex flex-col items-center justify-center min-h-[120px] ${isDocumentDragActive ? 'border-primary bg-primary/5' : 'border-gray-300'}`}
                        >
                            <input {...getDocumentInputProps()} />
                            <div className="w-full">
                                <div className="text-center text-gray-500 mb-3">
                                    {isDocumentDragActive ? (
                                        <span>{t("dropFiles")}</span>
                                    ) : (
                                        <span>
                                            {t("dragDrop")}{" "}
                                            <span className="underline">{t("browse")}</span>
                                        </span>
                                    )}
                                </div>
                                {mediaFormData.documents.length > 0 && (
                                    <div className="w-full space-y-2 mt-4">
                                        {documentFiles}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Video */}
                <div className="space-y-2">
                    <Label className="font-medium text-gray-800">
                        {t("videoLink")}
                    </Label>

                    {/* Video type selector */}
                    <Select value={videoType} onValueChange={(value) => handleVideoTypeChange(value)}>
                        <SelectTrigger className="w-full primaryBackgroundBg focus:ring-0">
                            <SelectValue placeholder={t("selectVideoType")} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="youtubeLink">
                                {t("youtube")}
                            </SelectItem>
                            <SelectItem value="vimeoLink">
                                {t("vimeo")}
                            </SelectItem>
                            {isCustomVideoUpload && (
                                <SelectItem value="customVideo">
                                    {t("customVideo")}
                                </SelectItem>
                            )}
                        </SelectContent>
                    </Select>

                    {/* URL input for YouTube / Vimeo */}
                    {(videoType === 'youtubeLink' || videoType === 'vimeoLink') && (
                        <Input
                            id="video-link"
                            type="text"
                            value={mediaFormData.videoLink}
                            onChange={handleVideoInputChange}
                            placeholder={
                                videoType === 'youtubeLink'
                                    ? (t("enterYoutubeVideoUrl"))
                                    : (t("enterVimeoVideoUrl"))
                            }
                            className="w-full px-3 py-2 primaryBackgroundBg rounded-md focus:outline-none focus:border-none focus:border-transparent"
                        />
                    )}

                    {/* Drag-and-drop zone for custom video */}
                    {videoType === 'customVideo' && isCustomVideoUpload && (
                        <div className="space-y-2">
                            {/* Existing server-side video (edit mode) */}
                            {isEditing && mediaFormData.videoLink && !mediaFormData.customVideo && (
                                <div className="relative rounded-md overflow-hidden border border-gray-200 bg-black">
                                    <video
                                        src={mediaFormData.videoLink}
                                        controls
                                        className="w-full max-h-[180px] object-contain"
                                    />
                                    <button
                                        type="button"
                                        className="absolute top-2 right-2 bg-black/70 hover:bg-black rounded-full p-1 text-white"
                                        onClick={() => {
                                            setMediaFormData(prev => ({ ...prev, videoLink: '' }))
                                            if (isEditing && setShouldRemoveVideo) {
                                                setShouldRemoveVideo(true);
                                            }
                                        }}
                                    >
                                        <FaTimes size={12} />
                                    </button>
                                </div>
                            )}

                            {/* Drop zone for new upload */}
                            <div className="primaryBackgroundBg rounded-md">
                                <div
                                    {...getVideoRootProps()}
                                    className={`border border-dashed p-4 rounded-md cursor-pointer transition-colors duration-200 flex flex-col items-center justify-center min-h-[120px] ${isVideoDragActive ? 'border-primary bg-primary/5' : 'border-gray-300'
                                        }`}
                                >
                                    <input {...getVideoInputProps()} />
                                    {mediaFormData.customVideo ? (
                                        <div className="flex items-center justify-between w-full px-2">
                                            <span className="text-sm text-gray-700 truncate">
                                                {mediaFormData.customVideo.name}
                                            </span>
                                            <button
                                                type="button"
                                                className="ml-2 bg-black rounded-full p-1 text-white hover:bg-gray-800 flex-shrink-0"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    setMediaFormData(prev => ({ ...prev, customVideo: null }));
                                                    if (isEditing && setShouldRemoveVideo && !mediaFormData.videoLink) {
                                                        setShouldRemoveVideo(true);
                                                    }
                                                }}
                                            >
                                                <FaTimes size={12} />
                                            </button>
                                        </div>
                                    ) : (
                                        <div className="text-center text-gray-500">
                                            {isVideoDragActive ? (
                                                <span>{t("dropFiles")}</span>
                                            ) : (
                                                <span>
                                                    {t("dragDrop")}{" "}
                                                    <span className="underline">{t("browse")}</span>
                                                </span>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Submit Button */}
            <div className="flex justify-end mt-8">
                <Button
                    onClick={() => handleTabChange(isProperty ? "facilities" : "location")}
                    className="px-10 py-5"
                >
                    {t("next")}
                </Button>
            </div>
        </div>
    );
};

export default ImagesVideoTab