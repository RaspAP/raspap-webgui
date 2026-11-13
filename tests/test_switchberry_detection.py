#!/usr/bin/env python3
"""Regression tests for KSZ9567-gated Switchberry detection."""

import importlib.machinery
import importlib.util
import pathlib
import tempfile
import unittest

CONTROLLER = (
    pathlib.Path(__file__).parents[1] / "config/switchberry/raspap-switchberryctl"
)
LOADER = importlib.machinery.SourceFileLoader("raspap_switchberryctl", str(CONTROLLER))
SPEC = importlib.util.spec_from_loader(LOADER.name, LOADER)
assert SPEC is not None
MODULE = importlib.util.module_from_spec(SPEC)
LOADER.exec_module(MODULE)


class SwitchberryDetectionTest(unittest.TestCase):
    def test_accepts_bound_ksz9567_driver(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            device = root / "sys/bus/spi/devices/spi0.0"
            (device / "of_node").mkdir(parents=True)
            (device / "of_node/compatible").write_bytes(b"microchip,ksz9567\0")
            (device / "driver").mkdir()

            self.assertTrue(MODULE.bound_ksz9567_present(root))
            self.assertEqual(MODULE.ksz9567_chip_id(root), MODULE.KSZ9567_CHIP_ID)

    def test_rejects_compatible_node_without_bound_driver(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            device = root / "sys/bus/spi/devices/spi0.0/of_node"
            device.mkdir(parents=True)
            (device / "compatible").write_bytes(b"microchip,ksz9567\0")

            self.assertFalse(MODULE.bound_ksz9567_present(root))

    def test_accepts_direct_spi_chip_id(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            chip_id = MODULE.ksz9567_chip_id(
                pathlib.Path(temporary),
                register_reader=lambda _address, _width: 0x009567A1,
            )
            self.assertEqual(chip_id, MODULE.KSZ9567_CHIP_ID)

    def test_rejects_other_spi_devices_and_software_only_images(self) -> None:
        with tempfile.TemporaryDirectory() as temporary:
            root = pathlib.Path(temporary)
            (root / "etc/switchberry").mkdir(parents=True)
            (root / "etc/startup-dpll.json").write_text("{}", encoding="utf-8")
            chip_id = MODULE.ksz9567_chip_id(
                root,
                register_reader=lambda _address, _width: 0x00947700,
            )
            self.assertIsNone(chip_id)


if __name__ == "__main__":
    unittest.main()
