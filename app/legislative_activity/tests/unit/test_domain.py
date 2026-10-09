"""Tests for legislative_activity domain entities and value objects."""

from datetime import date
import pytest

from app.legislative_activity.domain.entities import Proposition, Vote
from app.legislative_activity.domain.value_objects import VoteValue, PropositionType, LegislativeHouse, ExternalId


class TestVoteEntity:
    def test_create_vote(self):
        vote = Vote(
            politician_id=1,
            proposition_id=1,
            session_date=date(2023, 6, 15),
            vote_value=VoteValue.FAVOR,
        )
        assert vote.politician_id == 1
        assert vote.vote_value == VoteValue.FAVOR

    def test_vote_is_favorable(self):
        vote = Vote(politician_id=1, proposition_id=1, session_date=date(2023, 6, 15), vote_value=VoteValue.FAVOR)
        assert vote.is_favorable() is True

    def test_vote_is_against(self):
        vote = Vote(politician_id=1, proposition_id=1, session_date=date(2023, 6, 15), vote_value=VoteValue.CONTRA)
        assert vote.is_against() is True

    def test_vote_is_absent(self):
        vote = Vote(politician_id=1, proposition_id=1, session_date=date(2023, 6, 15), vote_value=VoteValue.AUSENTE)
        assert vote.is_absent() is True

    def test_vote_is_abstention(self):
        vote = Vote(politician_id=1, proposition_id=1, session_date=date(2023, 6, 15), vote_value=VoteValue.ABSTENCAO)
        assert vote.is_abstention() is True

    def test_vote_is_obstruction(self):
        vote = Vote(politician_id=1, proposition_id=1, session_date=date(2023, 6, 15), vote_value=VoteValue.OBSTRUCAO)
        assert vote.is_obstruction() is True

    def test_vote_is_art17(self):
        vote = Vote(politician_id=1, proposition_id=1, session_date=date(2023, 6, 15), vote_value=VoteValue.ART17)
        assert vote.is_art17() is True

    def test_vote_is_unknown(self):
        vote = Vote(politician_id=1, proposition_id=1, session_date=date(2023, 6, 15), vote_value=VoteValue.DESCONHECIDO)
        assert vote.is_unknown() is True


class TestPropositionEntity:
    def test_create_proposition(self):
        prop = Proposition(
            politician_id=1,
            external_id="PL-123-2023",
            type="PL",
            title="Projeto de Lei teste",
            house="camara",
        )
        assert prop.external_id == "PL-123-2023"
        assert prop.type == "PL"

    def test_proposition_is_pl(self):
        prop = Proposition(politician_id=1, external_id="PL-123-2023", type="PL", title="Teste", house="camara")
        assert prop.is_pl() is True


class TestVoteValueEnum:
    def test_all_values(self):
        values = VoteValue.all_values()
        assert "favor" in values
        assert "contra" in values
        assert "ausente" in values
        assert "abstencao" in values
        assert "obstrucao" in values
        assert "art17" in values
        assert "desconhecido" in values

    def test_vote_value_methods(self):
        assert VoteValue.FAVOR.is_favorable() is True
        assert VoteValue.CONTRA.is_against() is True
        assert VoteValue.AUSENTE.is_absent() is True
        assert VoteValue.ABSTENCAO.is_abstention() is True
        assert VoteValue.OBSTRUCAO.is_obstruction() is True
        assert VoteValue.ART17.is_art17() is True
        assert VoteValue.DESCONHECIDO.is_unknown() is True


class TestPropositionTypeEnum:
    def test_all_values(self):
        values = PropositionType.all_values()
        assert "PL" in values
        assert "PEC" in values
        assert "MPV" in values


class TestLegislativeHouseEnum:
    def test_all_values(self):
        values = LegislativeHouse.all_values()
        assert "camara" in values
        assert "senado" in values


class TestExternalIdValueObject:
    def test_valid_external_id(self):
        ext = ExternalId("PL-123-2023")
        assert str(ext) == "PL-123-2023"

    def test_empty_external_id_raises(self):
        with pytest.raises(ValueError):
            ExternalId("")