"""Politician domain value objects - immutable value types with validation."""

import re
from dataclasses import dataclass


@dataclass(frozen=True)
class CPF:
    """CPF value object with validation and formatting."""

    value: str

    def __post_init__(self):
        cleaned = self._clean(self.value)
        if not self._is_valid(cleaned):
            raise ValueError(f"Invalid CPF: {self.value}")
        object.__setattr__(self, "value", self._format(cleaned))

    @staticmethod
    def _clean(cpf: str) -> str:
        return re.sub(r"[^0-9]", "", cpf)

    @staticmethod
    def _is_valid(cpf: str) -> bool:
        if len(cpf) != 11:
            return False
        if cpf == cpf[0] * 11:
            return False

        # Validate first check digit
        sum_ = sum(int(cpf[i]) * (10 - i) for i in range(9))
        digit1 = (sum_ * 10 % 11) % 10
        if digit1 != int(cpf[9]):
            return False

        # Validate second check digit
        sum_ = sum(int(cpf[i]) * (11 - i) for i in range(10))
        digit2 = (sum_ * 10 % 11) % 10
        if digit2 != int(cpf[10]):
            return False

        return True

    @staticmethod
    def _format(cpf: str) -> str:
        return f"{cpf[:3]}.{cpf[3:6]}.{cpf[6:9]}-{cpf[9:]}"

    def __str__(self) -> str:
        return self.value


@dataclass(frozen=True)
class UF:
    """Brazilian state code (UF) value object."""

    VALID_UFS = frozenset([
        "AC", "AL", "AP", "AM", "BA", "CE", "DF", "ES", "GO", "MA",
        "MT", "MS", "MG", "PA", "PB", "PR", "PE", "PI", "RJ", "RN",
        "RS", "RO", "RR", "SC", "SP", "SE", "TO"
    ])

    value: str

    def __post_init__(self):
        upper = self.value.upper()
        if upper not in self.VALID_UFS:
            raise ValueError(f"Invalid UF: {self.value}. Must be one of {sorted(self.VALID_UFS)}")
        object.__setattr__(self, "value", upper)

    def __str__(self) -> str:
        return self.value


@dataclass(frozen=True)
class Party:
    """Political party value object."""

    value: str

    def __post_init__(self):
        cleaned = self.value.strip().upper()
        if not cleaned:
            raise ValueError("Party cannot be empty")
        if len(cleaned) > 100:
            raise ValueError("Party name too long (max 100 chars)")
        object.__setattr__(self, "value", cleaned)

    def __str__(self) -> str:
        return self.value


@dataclass(frozen=True)
class Badge:
    """Badge value object for politician achievements."""

    # Predefined badge types
    FICHA_LIMPA = "ficha_limpa"
    PRESENCA_ALTA = "presenca_alta"
    LEGISLADOR_ATIVO = "legislador_ativo"

    value: str

    def __post_init__(self):
        if not self.value:
            raise ValueError("Badge cannot be empty")
        object.__setattr__(self, "value", self.value.lower())

    def __str__(self) -> str:
        return self.value

    @classmethod
    def all_types(cls) -> list[str]:
        return [cls.FICHA_LIMPA, cls.PRESENCA_ALTA, cls.LEGISLADOR_ATIVO]
