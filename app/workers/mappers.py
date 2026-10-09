"""Mappers for Câmara API responses to domain entities."""

from datetime import date, datetime

from app.financial.domain.entities import Expense
from app.legislative_activity.domain.entities import Proposition, Vote
from app.legislative_activity.domain.value_objects import VoteValue
from app.politician.domain.entities import Mandate, Politician


def parse_date(value: str | None) -> date | None:
    """Parse date from string (ISO format or with time)."""
    if not value:
        return None
    # Handle ISO format with time: 2024-01-15T10:30:00
    if "T" in value:
        value = value.split("T")[0]
    # Handle datetime format: 2024-01-15 10:30:00
    if " " in value:
        value = value.split(" ")[0]
    try:
        return date.fromisoformat(value)
    except ValueError:
        return None


def parse_datetime(value: str | None) -> datetime | None:
    """Parse datetime from string."""
    if not value:
        return None
    try:
        return datetime.fromisoformat(value.replace("Z", "+00:00"))
    except ValueError:
        return None


def parse_amount(value: str | float | int | None) -> int:
    """Parse amount to centavos (integer).

    Handles:
    - Float: 1234.56 -> 123456
    - String with comma: "1.234,56" -> 123456
    - String with dot: "1234.56" -> 123456
    - Integer: 1234 -> 123400 (assumes reais)
    """
    if value is None:
        return 0
    if isinstance(value, (int, float)):
        # Assume value is in reais, convert to centavos
        return int(round(value * 100))
    if isinstance(value, str):
        # Remove currency symbols and spaces
        cleaned = value.strip().replace("R$", "").replace(" ", "")
        # Handle Brazilian format: 1.234,56
        if "," in cleaned and "." in cleaned:
            # Remove dots (thousands separator), replace comma with dot
            cleaned = cleaned.replace(".", "").replace(",", ".")
        elif "," in cleaned:
            # Only comma: 1234,56 -> 1234.56
            cleaned = cleaned.replace(",", ".")
        try:
            return int(round(float(cleaned) * 100))
        except ValueError:
            return 0
    return 0


def map_voto_value(voto: str) -> VoteValue:
    """Map Câmara API vote value to VoteValue enum.

    Câmara API values: Sim, Não, Abstenção, Ausente, Obstrução, Art. 17, etc.
    """
    if not voto:
        return VoteValue.DESCONHECIDO

    normalized = voto.strip().lower()

    # Map Câmara values to VoteValue
    mapping = {
        "sim": VoteValue.FAVOR,
        "não": VoteValue.CONTRA,
        "nao": VoteValue.CONTRA,
        "abstenção": VoteValue.ABSTENCAO,
        "abstencao": VoteValue.ABSTENCAO,
        "ausente": VoteValue.AUSENTE,
        "obstrução": VoteValue.OBSTRUCAO,
        "obstrucao": VoteValue.OBSTRUCAO,
        "art. 17": VoteValue.ART17,
        "art17": VoteValue.ART17,
        "art. 17 (voto de liderança)": VoteValue.ART17,
    }

    return mapping.get(normalized, VoteValue.DESCONHECIDO)


def map_deputado_to_politician(deputado: dict) -> Politician:
    """Map Câmara API deputado response to Politician entity."""
    return Politician(
        name=deputado.get("nome", ""),
        party=deputado.get("siglaPartido", ""),
        uf=deputado.get("siglaUf", ""),
        number=deputado.get("numero", 0) or 0,
        external_id=deputado.get("id"),
        cpf=deputado.get("cpf"),
        email=deputado.get("email"),
        photo_url=deputado.get("urlFoto"),
    )


def map_mandato_to_mandate(mandato: dict, politician_id: int) -> Mandate:
    """Map Câmara API mandato response to Mandate entity."""
    return Mandate(
        politician_id=politician_id,
        house="camara",
        role="deputado federal",
        uf=mandato.get("siglaUf", ""),
        start_date=parse_date(mandato.get("dataInicio")) or date.today(),
        end_date=parse_date(mandato.get("dataFim")),
        is_suplente=mandato.get("isSuplente", False) or False,
    )


def map_proposicao_to_proposition(
    proposicao: dict, politician_id: int
) -> Proposition:
    """Map Câmara API proposicao response to Proposition entity."""
    # Build external_id: {siglaTipo}-{numero}-{ano}
    sigla_tipo = proposicao.get("siglaTipo", "")
    numero = proposicao.get("numero", "")
    ano = proposicao.get("ano", "")
    external_id = f"{sigla_tipo}-{numero}-{ano}"

    return Proposition(
        politician_id=politician_id,
        external_id=external_id,
        type=sigla_tipo,
        title=proposicao.get("ementa", ""),
        house="camara",
        summary=proposicao.get("resumo"),
        status=proposicao.get("status"),
        presentation_date=parse_date(proposicao.get("dataApresentacao")),
        url=proposicao.get("urlInteiroTeor"),
    )


def map_voto_to_vote(
    voto: dict, politician_id: int, proposition_id: int
) -> Vote:
    """Map Câmara API voto response to Vote entity."""
    return Vote(
        politician_id=politician_id,
        proposition_id=proposition_id,
        session_date=parse_date(voto.get("dataSessao")) or date.today(),
        vote_value=map_voto_value(voto.get("voto", "")),
        session_number=voto.get("numeroSessao"),
    )


def map_despesa_to_expense(despesa: dict, politician_id: int) -> Expense:
    """Map Câmara API despesa response to Expense entity."""
    expense_date = parse_date(despesa.get("dataDocumento")) or date.today()

    return Expense(
        politician_id=politician_id,
        expense_type=despesa.get("tipoDespesa", ""),
        amount=parse_amount(despesa.get("valorDocumento")),
        expense_date=expense_date,
        year=expense_date.year,
        month=expense_date.month,
        description=despesa.get("descricao"),
        provider=despesa.get("nomeFornecedor"),
        document_number=despesa.get("numeroDocumento"),
        document_url=despesa.get("urlDocumento"),
    )


def build_external_id_map(
    items: list, id_field: str = "id"
) -> dict[int, int]:
    """Build a map from external_id to internal_id.

    Args:
        items: List of domain entities with id and external_id
        id_field: Field name for external_id (default: "id")

    Returns:
        Dict mapping external_id -> internal_id
    """
    result = {}
    for item in items:
        external_id = getattr(item, "external_id", None)
        internal_id = getattr(item, "id", None)
        if external_id is not None and internal_id is not None:
            result[external_id] = internal_id
    return result
