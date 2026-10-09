from contextlib import asynccontextmanager

from fastapi import FastAPI, Request
from fastapi.responses import JSONResponse

from app.engagement.api.router import router as engagement_router
from app.financial.api.router import router as financial_router
from app.ingestion.api.router import router as ingestion_router
from app.legislative_activity.api.router import router as legislative_router
from app.politician.api.router import router as politician_router
from app.shared.kernel.config import settings
from app.shared.kernel.database import close_db, init_db
from app.shared.kernel.exceptions import (
    BusinessRuleError,
    ConflictError,
    DomainError,
    NotFoundError,
    ValidationError,
)


@asynccontextmanager
async def lifespan(_app: FastAPI):
    await init_db()
    yield
    await close_db()


app = FastAPI(
    title="Meu Candidato API",
    description="API para agregação de dados de políticos brasileiros",
    version="0.1.0",
    lifespan=lifespan,
)

app.include_router(politician_router, prefix="/api/v1")
app.include_router(legislative_router, prefix="/api/v1")
app.include_router(financial_router, prefix="/api/v1")
app.include_router(engagement_router, prefix="/api/v1")
app.include_router(ingestion_router, prefix="/api/v1")


@app.exception_handler(NotFoundError)
async def not_found_error_handler(_request: Request, exc: NotFoundError):
    return JSONResponse(
        status_code=404,
        content={
            "error": exc.code,
            "message": exc.message,
            "resource": exc.details.get("resource"),
            "identifier": exc.details.get("identifier"),
        },
    )


@app.exception_handler(ValidationError)
async def validation_error_handler(_request: Request, exc: ValidationError):
    return JSONResponse(
        status_code=422,
        content={"error": exc.code, "message": exc.message, "field": exc.details.get("field")},
    )


@app.exception_handler(ConflictError)
async def conflict_error_handler(_request: Request, exc: ConflictError):
    return JSONResponse(
        status_code=409,
        content={
            "error": exc.code,
            "message": exc.message,
            "resource": exc.details.get("resource"),
            "field": exc.details.get("field"),
            "value": exc.details.get("value"),
        },
    )


@app.exception_handler(BusinessRuleError)
async def business_rule_error_handler(_request: Request, exc: BusinessRuleError):
    return JSONResponse(
        status_code=400,
        content={
            "error": exc.code,
            "message": exc.message,
            "rule": exc.details.get("rule"),
            "details": exc.details.get("details"),
        },
    )


@app.exception_handler(DomainError)
async def domain_error_handler(_request: Request, exc: DomainError):
    return JSONResponse(
        status_code=500,
        content={"error": exc.code, "message": exc.message},
    )


@app.get("/health")
async def health():
    return {"status": "ok", "environment": settings.environment}