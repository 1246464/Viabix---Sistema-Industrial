package com.viabix.app.data.api

import org.junit.Assert.assertEquals
import org.junit.Test

class ApiModelsTest {
    @Test
    fun projectProgressUsesSameWeightsAsWebApp() {
        val project = ProjectSummary(
            tasks = mapOf(
                "kom" to ProjectTask(),
                "tryout" to ProjectTask(planned = "2026-09-01", executed = "2026-09-03"),
                "psw" to ProjectTask(planned = "2026-09-10")
            )
        )

        assertEquals(25, project.progressPercent)
    }

    @Test
    fun projectWithoutScheduledTasksStartsAtZero() {
        val project = ProjectSummary(tasks = mapOf("kom" to ProjectTask()))

        assertEquals(0, project.progressPercent)
    }

    @Test
    fun completedProjectReachesOneHundredPercent() {
        val completed = listOf("kom", "ferramental", "cadBomFt", "tryout", "entrega", "psw", "handover")
            .associateWith { ProjectTask(executed = "2026-09-29") }

        assertEquals(100, ProjectSummary(tasks = completed).progressPercent)
    }
}
