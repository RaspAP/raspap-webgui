import os
import secrets
from fastapi.security.api_key import APIKeyHeader
from fastapi import Security, HTTPException
from starlette.status import HTTP_403_FORBIDDEN
from dotenv import load_dotenv

# systemd passes the key from .env (EnvironmentFile), so the service can read
# it also when it can not read .env itself
try:
    load_dotenv()
except OSError:
    pass

# Without a key in .env, the API refuses each request. The installer makes a
# key, and the RestAPI page of the web interface can change it.
apikey = os.getenv('RASPAP_API_KEY') or ''

api_key_header = APIKeyHeader(name="access_token", auto_error=False)

async def get_api_key(api_key_header: str = Security(api_key_header)):
    if apikey and api_key_header and secrets.compare_digest(api_key_header.encode(), apikey.encode()):
        return api_key_header
    raise HTTPException(
        status_code=HTTP_403_FORBIDDEN, detail="403: Unauthorized"
    )
