from werkzeug.datastructures import FileStorage

from api import gallery_ns
from api.models.galleryModel import GALLERY_CATEGORIES

# Gallery uses form-data (because of the image file), so these are parsers.

# Create Gallery Item Payload
create_gallery_payload = gallery_ns.parser()
create_gallery_payload.add_argument('title', type=str, required=True, location='form', help='Image title')
create_gallery_payload.add_argument('category', type=str, required=True, location='form',
                                    choices=list(GALLERY_CATEGORIES.keys()), help='Category')
create_gallery_payload.add_argument('description', type=str, required=False, location='form', help='Description')
create_gallery_payload.add_argument('sort_order', type=int, required=False, default=0, location='form', help='Display order')
create_gallery_payload.add_argument('image', type=FileStorage, required=True, location='files', help='Image file')

# Update Gallery Item Payload (all fields optional, send only what changes)
update_gallery_payload = gallery_ns.parser()
update_gallery_payload.add_argument('title', type=str, required=False, location='form', help='Image title')
update_gallery_payload.add_argument('category', type=str, required=False, location='form',
                                    choices=list(GALLERY_CATEGORIES.keys()), help='Category')
update_gallery_payload.add_argument('description', type=str, required=False, location='form', help='Description')
update_gallery_payload.add_argument('sort_order', type=int, required=False, location='form', help='Display order')
update_gallery_payload.add_argument('status', type=str, required=False, location='form',
                                    choices=['active', 'inactive'], help='Show / hide on website')
update_gallery_payload.add_argument('image', type=FileStorage, required=False, location='files', help='New image file')
