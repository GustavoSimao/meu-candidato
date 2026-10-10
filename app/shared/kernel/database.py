"""Shared kernel database - base SQLAlchemy setup and session management."""

import asyncio
import sys

from sqlalchemy.ext.asyncio import AsyncSession, async_sessionmaker, create_async_engine
from sqlalchemy.orm import DeclarativeBase

from app.shared.kernel.config import get_database_url, settings


class Base(DeclarativeBase):
    pass


def _create_engine():
    return create_async_engine(
        get_database_url(),
        echo=settings.environment == "development",
        pool_pre_ping=True,
        pool_size=10,
        max_overflow=20,
    )


engine = _create_engine()

async_session_maker = async_sessionmaker(
    engine,
    class_=AsyncSession,
    expire_on_commit=False,
)


async def get_session() -> AsyncSession:
    async with async_session_maker() as session:
        yield session


async def init_db() -> None:
    """Run Alembic migrations on startup.

    Uses a subprocess to invoke ``alembic upgrade head`` asynchronously.
    This avoids nesting ``asyncio.run()`` (called inside ``env.py`` by
    ``command.upgrade``) within the already-running uvicorn event loop,
    which previously caused a ``RuntimeWarning: coroutine ... was never
    awaited`` and uvicorn exit code 3.
    """
    proc = await asyncio.create_subprocess_exec(
        sys.executable, "-m", "alembic", "upgrade", "head",
        stdout=asyncio.subprocess.PIPE,
        stderr=asyncio.subprocess.PIPE,
    )
    stdout, stderr = await proc.communicate()
    if proc.returncode != 0:
        raise RuntimeError(
            f"Alembic migration failed (exit {proc.returncode}):\n"
            f"stdout: {stdout.decode()}\n"
            f"stderr: {stderr.decode()}"
        )


async def close_db() -> None:
    await engine.dispose()
