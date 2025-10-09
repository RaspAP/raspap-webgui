import os
from fastapi.security.api_key import APIKeyHeader
from fastapi import Security, HTTPException
from starlette.status import HTTP_403_FORBIDDEN
from dotenv import load_dotenv

load_dotenv()

apikey=os.getenv('RASPAP_API_KEY')
#if env not set, set the api key to "insecure"
if apikey == None:
    apikey = "insecure"

print(apikey)
api_key_header = APIKeyHeader(name="access_token", auto_error=False)

async def get_api_key(api_key_header: str = Security(api_key_header)):
    if api_key_header ==apikey:
        return api_key_header   
    else:
        raise HTTPException(
            status_code=HTTP_403_FORBIDDEN, detail="403: Unauthorized"
        )

