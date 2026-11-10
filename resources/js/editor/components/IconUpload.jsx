import { MediaUpload, MediaUploadCheck } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import { Icon } from "./Icon";
import { useMediaUrl } from "./MediaField";

/**
 * Icon tile that opens the media library. Shows the uploaded image, or the item's
 * bundled illustration (`fallback`, a name in resources/images/icons/featherdash) when none is set.
 */
export const IconUpload = ({ value, onChange, fallback, className, size }) => {
  const url = useMediaUrl(value);

  return (
    <MediaUploadCheck>
      <MediaUpload
        allowedTypes={["image"]}
        value={value}
        onSelect={(image) => onChange(image.id)}
        render={({ open }) => (
          <span className="relative self-start">
            <button
              type="button"
              className={`${className} cursor-pointer border-0`}
              onClick={open}
              aria-label={value ? __("Replace icon", "radicle") : __("Upload icon", "radicle")}
            >
              {url ? (
                <img src={url} alt="" style={{ width: size, height: size, objectFit: "contain" }} />
              ) : fallback ? (
                <Icon name={fallback} size={size} />
              ) : (
                <span className="text-xs font-bold opacity-60">{__("Icon", "radicle")}</span>
              )}
            </button>
            {value > 0 && (
              <button
                type="button"
                onClick={() => onChange(0)}
                className="absolute -top-2 -right-2 flex size-6 cursor-pointer items-center justify-center rounded-full border-0 bg-white text-xs text-[#b42318] shadow ring-1 ring-line"
                aria-label={__("Remove icon", "radicle")}
              >
                ✕
              </button>
            )}
          </span>
        )}
      />
    </MediaUploadCheck>
  );
};
