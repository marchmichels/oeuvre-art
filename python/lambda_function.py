import boto3
from PIL import Image
from scipy import spatial
import numpy as np
import io

s3_client = boto3.client('s3')

def lambda_handler(event, context):

    bucket_name = 'oeuvreart-python-base'
    file_name = 'python_base_img1.png'
    
    base_image = s3_client.get_object(Bucket=bucket_name, Key=file_name)
    base_image = base_image.get('Body').read()
    base_image = Image.open(io.BytesIO(base_image))
    base_image = base_image.resize((2560,1706))
    base_image = base_image.convert('RGB')

    # Fill array art_photos with resized images from S3
    art_photos = []

    response = s3_client.list_objects_v2(Bucket='oeuvreartbucket', Prefix="art_image/")
    files = response.get("Contents")

    for file in files:
        cur_image = s3_client.get_object(Bucket='oeuvreartbucket', Key=file['Key']).get('Body').read()
        cur_image = Image.open(io.BytesIO(cur_image))
        cur_image = cur_image.convert('RGB')
        cur_image = cur_image.resize((25,25))
        art_photos.append(cur_image)
        
    # Calculate average color of each image from S3
    colors = []
    for tile in art_photos:
        mean_color = np.array(tile).mean(axis=0).mean(axis=0)
        colors.append(mean_color)

    # Pixelate base photo
    width = int(np.round(base_image.size[0] / 25))
    height = int(np.round(base_image.size[1] / 25))

    resized_photo = base_image.resize((width, height))

    # Find closest matching color image from S3 for each pixel in base image
    tree = spatial.KDTree(colors)
    closest_tiles = np.zeros((width, height), dtype=np.uint32)

    for i in range(width):
        for j in range(height):
            closest = tree.query(resized_photo.getpixel((i, j)))
            closest_tiles[i, j] = closest[1]

    # Create an empty new image the same size as the base photo
    output = Image.new('RGB', base_image.size)

    # Paste matching images from S3 at the correct location to create the mosaic
    for i in range(width):
        for j in range(height):
            # Offset of tile
            x, y = i*25, j*25
            # Index of tile
            index = closest_tiles[i, j]
            # Draw tile
            output.paste(art_photos[index], (x, y))

    # Save generated image
    buffer = io.BytesIO()
    output.save(buffer, format='JPEG')
    output_byte = buffer.getvalue()

    s3_client.upload_fileobj(io.BytesIO(output_byte), 'oeuvreart-python-output', 'python_mosaic.jpg')