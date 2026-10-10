package com.viabix.app.presentation.forms

import com.google.gson.JsonArray
import com.google.gson.JsonObject
import com.viabix.app.data.api.ProjectLeader
import com.viabix.app.presentation.screens.anvis.AnviFormUiState
import com.viabix.app.presentation.screens.anvis.buildAnviId
import com.viabix.app.presentation.screens.anvis.buildAnviGuidance
import com.viabix.app.presentation.screens.anvis.buildAnviPayload
import com.viabix.app.presentation.screens.projects.ProjectFormUiState
import com.viabix.app.presentation.screens.projects.ProjectTaskFormState
import com.viabix.app.presentation.screens.projects.ApqpAnswerFormState
import com.viabix.app.presentation.screens.projects.buildProjectPayload
import com.viabix.app.presentation.screens.projects.buildProjectGuidance
import com.viabix.app.presentation.screens.projects.calculateProjectStatus
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class FormPayloadTest {
    @Test
    fun anviEditPreservesFinancialDataAndDoesNotMutateSource() {
        val source = JsonObject().apply {
            addProperty("id", "ANVI-10_R1")
            addProperty("cliente", "Cliente antigo")
            add("financeiro", JsonObject().apply {
                add("investimentos", JsonObject().apply {
                    addProperty("investimento_total", 125000)
                    addProperty("ferramental", 48000)
                })
            })
            add("configuracoes", JsonObject().apply { addProperty("margemLucroMarkup", 18) })
        }

        val payload = buildAnviPayload(
            source,
            AnviFormUiState(
                id = "ANVI-10_R1",
                numero = "ANVI-10",
                revisao = "R1",
                cliente = "Cliente novo",
                volumeMensal = "2500",
                margemLucroMarkup = "25",
                custoUnitario = "12,50",
                investimentoTotal = "130000",
                viabilidadeTecnica = true
            )
        )

        assertEquals("Cliente antigo", source.get("cliente").asString)
        assertEquals("Cliente novo", payload.get("cliente").asString)
        assertEquals(125000, source.getAsJsonObject("financeiro").getAsJsonObject("investimentos").get("investimento_total").asInt)
        assertEquals(130000, payload.getAsJsonObject("financeiro").getAsJsonObject("investimentos").get("investimento_total").asInt)
        assertEquals(48000, payload.getAsJsonObject("financeiro").getAsJsonObject("investimentos").get("ferramental").asInt)
        assertEquals(12.5, payload.getAsJsonObject("financeiro").getAsJsonObject("custos").get("custo_unitario").asDouble, 0.001)
        assertEquals(25, payload.getAsJsonObject("configuracoes").get("margemLucroMarkup").asInt)
        assertTrue(payload.getAsJsonObject("checkboxes").get("viabilidadeTecnica").asBoolean)
    }

    @Test
    fun anviIdIsSafeAndLimitedToDatabaseColumn() {
        val id = buildAnviId("ANVI 2026/001", "REV 01")

        assertEquals("ANVI-2026-001_REV-01", id)
        assertTrue(id.length <= 50)
    }

    @Test
    fun projectEditPreservesTasksAndApqp() {
        val source = JsonObject().apply {
            addProperty("id", 42)
            addProperty("projectName", "Projeto antigo")
            add("tasks", JsonObject().apply {
                add("tryout", JsonObject().apply { addProperty("executed", "2026-09-29") })
            })
            add("apqp", JsonObject().apply { addProperty("f1_q1", true) })
        }
        val state = ProjectFormUiState(
            id = 42,
            cliente = "Cliente",
            projectName = "Projeto atualizado",
            leaderId = "7",
            leaders = listOf(ProjectLeader(id = 7, name = "Líder de projeto"))
        )

        val payload = buildProjectPayload(source, state)

        assertEquals("Projeto antigo", source.get("projectName").asString)
        assertEquals("Projeto atualizado", payload.get("projectName").asString)
        assertEquals("Líder de projeto", payload.get("projectLeader").asString)
        assertEquals("2026-09-29", payload.getAsJsonObject("tasks").getAsJsonObject("tryout").get("executed").asString)
        assertTrue(payload.getAsJsonObject("apqp").get("f1_q1").asBoolean)
    }

    @Test
    fun changingProjectAnviNumberClearsStaleInternalLink() {
        val source = JsonObject().apply {
            addProperty("anviNumber", "ANVI-ANTIGA")
            addProperty("anviId", "ANVI-ANTIGA_R1")
            addProperty("anviRevision", "R1")
        }
        val state = ProjectFormUiState(
            cliente = "Cliente",
            projectName = "Projeto",
            leaderId = "1",
            anviNumber = "ANVI-NOVA",
            leaders = listOf(ProjectLeader(id = 1, name = "Líder"))
        )

        val payload = buildProjectPayload(source, state)

        assertTrue(!payload.has("anviId"))
        assertTrue(!payload.has("anviRevision"))
        assertEquals("manual", payload.getAsJsonObject("sourceContext").get("source").asString)
        assertEquals("ANVI-NOVA", payload.getAsJsonObject("sourceContext").get("anviNumber").asString)
    }

    @Test
    fun projectTaskEditPreservesResourcesAndHistory() {
        val source = JsonObject().apply {
            add("tasks", JsonObject().apply {
                add("tryout", JsonObject().apply {
                    addProperty("number", "T-99")
                    add("resources", JsonObject().apply { addProperty("corte", "Linha A") })
                    add("history", JsonArray().apply { add("registro existente") })
                })
            })
        }
        val state = ProjectFormUiState(
            cliente = "Cliente",
            projectName = "Projeto",
            leaderId = "1",
            tasks = mapOf(
                "tryout" to ProjectTaskFormState(
                    planned = "2026-10-10",
                    start = "2026-10-11",
                    executed = "2026-10-12",
                    duration = "4",
                    number = "T-99",
                    resources = mapOf("corte" to "Linha A")
                )
            )
        )

        val payload = buildProjectPayload(source, state)
        val tryout = payload.getAsJsonObject("tasks").getAsJsonObject("tryout")

        assertEquals("2026-10-10", tryout.get("planned").asString)
        assertEquals("2026-10-11", tryout.get("start").asString)
        assertEquals("2026-10-12", tryout.get("executed").asString)
        assertEquals(4, tryout.get("duration").asInt)
        assertEquals("T-99", tryout.get("number").asString)
        assertEquals("Linha A", tryout.getAsJsonObject("resources").get("corte").asString)
        assertEquals("registro existente", tryout.getAsJsonArray("history")[0].asString)
        assertTrue(!source.getAsJsonObject("tasks").getAsJsonObject("tryout").has("planned"))
    }

    @Test
    fun automaticProjectStatusFollowsWebPriority() {
        val tasks = mapOf(
            "kom" to ProjectTaskFormState(executed = "2026-09-20"),
            "tryout" to ProjectTaskFormState(planned = "2026-09-25", start = "2026-09-24"),
            "psw" to ProjectTaskFormState(planned = "2026-10-10")
        )

        assertEquals("Atrasado", calculateProjectStatus(tasks, today = "2026-09-29"))
    }

    @Test
    fun projectAdvancedFieldsUseWebCompatibleJson() {
        val source = JsonObject().apply {
            add("apqp", JsonObject().apply {
                add("kom", JsonObject().apply {
                    add("answers", JsonObject().apply {
                        add("custom_q", JsonObject().apply { addProperty("answer", "sim") })
                    })
                })
            })
        }
        val state = ProjectFormUiState(
            cliente = "Cliente",
            projectName = "Projeto",
            leaderId = "1",
            tasks = mapOf(
                "ferramental" to ProjectTaskFormState(
                    duration = "5",
                    resources = mapOf("matriz" to "2026-10-20")
                ),
                "tryout" to ProjectTaskFormState(
                    duration = "3",
                    number = "TR-7",
                    quantidadeEntrada = "100",
                    quantidadeSaida = "96",
                    resources = mapOf("fornos" to "HTF")
                )
            ),
            apqp = mapOf(
                "kom" to mapOf(
                    "f1_q1" to ApqpAnswerFormState(answer = "sim", observations = "ANVI aprovada")
                )
            )
        )

        val payload = buildProjectPayload(source, state)
        val tasks = payload.getAsJsonObject("tasks")
        val tryout = tasks.getAsJsonObject("tryout")
        val komAnswers = payload.getAsJsonObject("apqp").getAsJsonObject("kom").getAsJsonObject("answers")

        assertEquals("2026-10-20", tasks.getAsJsonObject("ferramental").getAsJsonObject("resources").get("matriz").asString)
        assertEquals("TR-7", tryout.get("number").asString)
        assertEquals(100, tryout.get("quantidadeEntrada").asInt)
        assertEquals(96, tryout.get("quantidadeSaida").asInt)
        assertEquals("HTF", tryout.getAsJsonObject("resources").get("fornos").asString)
        assertEquals("sim", komAnswers.getAsJsonObject("f1_q1").get("answer").asString)
        assertEquals("ANVI aprovada", komAnswers.getAsJsonObject("f1_q1").get("observations").asString)
        assertTrue(komAnswers.has("custom_q"))
    }

    @Test
    fun anviGuidanceWarnsWhenPriceDoesNotCoverCost() {
        val summary = buildAnviGuidance(
            AnviFormUiState(
                numero = "ANVI-1",
                revisao = "R1",
                cliente = "Cliente",
                projeto = "Projeto",
                produto = "Produto",
                volumeMensal = "100",
                dataAnvi = "2026-09-30",
                custoUnitario = "120",
                precoSugerido = "100"
            )
        )

        assertTrue(summary.warnings.any { it.contains("preço sugerido") })
        assertTrue(summary.pending.any { it.contains("totais mensais") })
    }

    @Test
    fun projectGuidanceListsScheduleAndApqpPendingItems() {
        val summary = buildProjectGuidance(
            ProjectFormUiState(
                cliente = "Cliente",
                projectName = "Projeto",
                leaderId = "1",
                tasks = mapOf("kom" to ProjectTaskFormState(planned = "2026-10-01"))
            )
        )

        assertTrue(summary.pending.any { it.contains("Ferramental") })
        assertTrue(summary.pending.any { it.contains("APQP de KOM") })
    }
}
