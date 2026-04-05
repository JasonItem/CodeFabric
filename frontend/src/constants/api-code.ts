export enum ApiCode {
  SUCCESS = 200,
  BAD_REQUEST = 400,
  UNAUTHORIZED = 401,
  FORBIDDEN = 403,
  NOT_FOUND = 404,
  CONFLICT = 409,
  VALIDATION_ERROR = 422,
  REPEAT_SUBMIT = 429,
  SERVER_ERROR = 500,
}

export function isApiSuccess(code: number | undefined | null) {
  return code === ApiCode.SUCCESS
}
