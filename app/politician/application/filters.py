"""Politician application filter DTO."""


from pydantic import BaseModel, ConfigDict, Field


class PoliticianFilterDTO(BaseModel):
    """Filters for listing politicians."""

    page: int = Field(default=1, ge=1, description="Page number (1-indexed)")
    per_page: int = Field(default=20, ge=1, le=100, description="Items per page")
    uf: str | None = Field(default=None, description="Filter by UF")
    party: str | None = Field(default=None, description="Filter by party (partial match)")

    model_config = ConfigDict(extra="forbid")
