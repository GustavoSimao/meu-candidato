"""Politician application DTOs - Pydantic schemas for API requests/responses."""

from datetime import date

from pydantic import BaseModel, ConfigDict


class MandateDTO(BaseModel):
    """DTO for a political mandate."""

    house: str
    role: str
    uf: str
    start_date: date
    end_date: date | None = None
    is_suplente: bool = False

    model_config = ConfigDict(from_attributes=True)


class PoliticianBaseDTO(BaseModel):
    """Base DTO with common politician fields."""

    name: str
    party: str
    uf: str
    number: int
    external_id: int | None = None
    cpf: str | None = None
    email: str | None = None
    office_address: str | None = None
    office_phone: str | None = None
    biography: str | None = None
    social_media: dict | None = None
    education: str | None = None
    photo_url: str | None = None


class PoliticianCreateDTO(PoliticianBaseDTO):
    """DTO for creating a politician."""

    pass


class PoliticianUpdateDTO(BaseModel):
    """DTO for updating a politician (all fields optional)."""

    name: str | None = None
    party: str | None = None
    uf: str | None = None
    number: int | None = None
    cpf: str | None = None
    email: str | None = None
    office_address: str | None = None
    office_phone: str | None = None
    biography: str | None = None
    social_media: dict | None = None
    education: str | None = None
    photo_url: str | None = None


class PoliticianListItemDTO(PoliticianBaseDTO):
    """DTO for politician list items."""

    id: int
    badges: list[str] = []

    model_config = ConfigDict(from_attributes=True)


class PoliticianListDTO(BaseModel):
    """DTO for paginated politician list."""

    items: list[PoliticianListItemDTO]
    total: int
    page: int
    per_page: int
    pages: int


class PoliticianDetailDTO(PoliticianBaseDTO):
    """DTO for politician detail with mandates."""

    id: int
    mandates: list[MandateDTO] = []
    badges: list[str] = []

    model_config = ConfigDict(from_attributes=True)
